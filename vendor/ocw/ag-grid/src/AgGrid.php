<?php

namespace Ocw\AgGrid;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Ocw\AgGrid\Processors\DataProcessor;

class AgGrid
{
    public $request;
    public $keepSelectBindings = true;
    public $model;

    protected $appends = [];
    protected $filter_columns = [];
    protected $default_order = ['id'];
    protected $is_default_order_enabled = true;
    protected $extra_info = [];
    protected $skip_filter_columns = [];
    protected $latest_query_column_name = null ;

    protected $columnDef = [
        'index'       => false,
        'append'      => [],
        'edit'        => [],
        'filter'      => [],
        'order'       => [],
        'only'        => null,
        'hidden'      => [],
        'visible'     => [],
        'excess'      => [],
        'escape'      => [],
        'raw'         => [],
    ];


    public $total_count = 0;
    public $query_generated_time = 0 ;

    public function __construct()
    {
        $this->request    = request();
        //$this->config     = app('datatables.config');
        //$this->columns    = $builder->columns;
    }

    public function of(Builder $builder)
    {
        $this->query      = $builder;
        $this->connection = $builder->getConnection();
        return $this;
    }

    public function noData(){
        return [
            'rowCount' => 0,
            'rowData' => [],
            'storeInfo' => []
        ];
    }

    protected function attachAppends(array $data)
    {
        return array_merge($data, $this->appends);
    }

    protected function showDebugger(array $output)
    {
        $output['debug']['input'] = $this->request->all();
        $output['debug']['query'] = $this->queryx;
        return $output;
    }

    protected function render($data)
    {
        $output = $this->attachAppends([
            //'rowTotal'    => $this->total_count,
            //'recordsFiltered' => $this->filteredRecords,
            'rowData'            => $data,
            'rowCount'           => $this->total_count,
            'storeInfo'          => $this->extra_info ,
            'queryGeneratedTime' => $this->query_generated_time
            //'query' => $this->queryx
        ]);

        if ($this->isDebugging()) {
            $output = $this->showDebugger($output);
        }

        // foreach ($this->searchPanes as $column => $searchPane) {
        //     $output['searchPanes']['options'][$column] = $searchPane['options'];
        // }
        return $output;
        // return new JsonResponse(
        //     $output,
        //     200,
        // );
    }

    public function addColumn($name, $content, $order = false)
    {
        $this->extraColumns[] = $name;

        $this->columnDef['append'][] = ['name' => $name, 'content' => $content, 'order' => $order];

        return $this;
    }

    public function editColumn($name, $content)
    {
        $this->columnDef['edit'][] = ['name' => $name, 'content' => $content];

        return $this;
    }

    public function removeColumn()
    {
        $names                     = func_get_args();
        $this->columnDef['excess'] = array_merge($this->columnDef['excess'], $names);

        return $this;
    }

    function isDebugging()
    {
        return config('app.debug') == true;
    }

    function with($data = []){
        $this->extra_info = $data;
        return $this;
    }

    function make()
    {
        $this->filter();
        
        $this->latestQueryDataFilter() ;
        $this->getTotal();
        $this->ordering();
        $this->paginate();
        $this->queryGeneratedTime() ;


        $results   = $this->results();
        $processed = $this->processResults($results);

        return $this->render($processed);
    }

    public function results()
    {
        return $this->query->get();
    }

    protected function processResults($results)
    {
        $processor = new DataProcessor(
            $results,
            $this->columnDef,
            $this->request->input('startRow')
        );

        return $processor->process(true);
    }

    public function getTotal(){
        if($this->isDebugging()){
            $query = clone $this->query;
            try {
                $this->queryx =  vsprintf(str_replace('?', '%s', $query->toSql()), collect($query->getBindings())->map(function ($binding) {
                    return is_numeric($binding) ? $binding : "'{$binding}'";
                })->toArray());
            }
            catch (Exception $e){
                $this->queryx = "Error in query Generation: ".$e->getMessage();
            }
        }

        $this->total_count = $this->count();
    }

    public function count()
    {
        return $this->prepareCountQuery()->count();
    }

    public function paginate()
    {
        if ($this->request->has('startRow')) {
            $startRow = $this->request->startRow;
            $endRow = $this->request->endRow;
            $pageSize = $endRow - $startRow;
            if ($pageSize > 0)
                $this->query->skip($startRow)->take($pageSize);
            else if($pageSize < 0) //when startRow grater than endRow which should not happen
                $this->query->take(0);
            else
                $this->query->take(10);
        } else
            $this->query->take(10);
    }

    public function ordering()
    {

        $sort_model = $this->request->input('sortModel');

        if (!empty($sort_model)) {
            foreach ($sort_model as $value) {
                $this->query->orderBy($value['colId'], $value['sort']);
            }
        }
        else {
            if($this->is_default_order_enabled)
                $this->defaultOrdering();
        }
    }

    public function withOutDefaultOrderBy(){

        $this->is_default_order_enabled = false;
        return $this;
    }

    public function withDefaultOrderBy($order){
        $this->default_order = $order;

        return $this;
    }

    protected function defaultOrdering(){
        if(!empty($this->default_order))
            $this->query->orderBy($this->default_order[0], $this->default_order[1]??'desc');
    }

    public function filter()
    {

        $filter_model = $this->request->filterModel;

        $where_parts = [];
        if ($filter_model)
            foreach ($filter_model as $key => $value) {
                //$item = $filter_model[$key];
                //skip filter for skip filter column
                if(!in_array($key,$this->skip_filter_columns))
                {
                    if(!isset($this->filter_columns[$key]))
                        $where_parts[] = $this->createFilterData($key, $value);
                    else
                        $this->applyFilterColumn($this->query, $key, $value['filter']);
                }
                    
            }

        if (!empty(array_filter($where_parts))) {
            // foreach($where_parts as $where){
            $this->query->where(array_filter($where_parts));
            // }
        }

    }

    public function filterColumn($column, callable $callback)
    {
        $this->filter_columns[$column] = ['method' => $callback];

        return $this;
    }

    public function skipFilterColumns($columns)
    {
        $this->skip_filter_columns = $columns;
        return $this;
    }

    public function createFilterData($key, $item)
    {
        switch ($item['filterType']) {
            case 'text':
                if (isset($item['type']) and $item['type'] == 'domainsFilter') {
                    //return $this->createDomainsFilter($key,$item['filter']);
                } else {
                    if ($item['filter'] === 'isnull') {
                        $this->query->whereNull($key);
                        return [];
                    } elseif ($item['filter'] === 'isnotnull') {
                        $this->query->whereNotNull($key);
                        return [];
                    } else {
                        return $this->createTextFilter($key, $item);
                    }
                }
            case 'number':
                return $this->createNumberFilter($key, $item);
            case 'date':
                return $this->createDateFilter($key, $item);
            case 'set':
                return $this->createSetFilter($key, $item);
            default:
                logger('unkonwn filter type: ' . $item['filterType']);
        }
    }


    public function createTextFilter($key, $item)
    {
        switch ($item['type']) {
            case 'equals':
                return [$key, '=', $item['filter']];
            case 'notEqual':
                return [$key, '!=',  $item['filter']];
            case 'contains':
                return [$key, 'like', '%' . $item['filter'] . '%'];
            case 'notContains':
                return [$key, '!=', '%' . $item['filter'] . '%'];
            case 'startsWith':
                return [$key, 'like', $item['filter'] . '%'];
            case 'endsWith':
                return [$key, 'like', '%' . $item['filter']];
            default:
                return [];
        }
    }

    public function createNumberFilter($key, $item)
    {
        switch ($item['type']) {
            case 'equals':
                return [$key, '=', $item['filter']];
            case 'notEqual':
                return [$key, '!=', $item['filter']];
            case 'greaterThan':
                return [$key, '>', $item['filter']];
            case 'greaterThanOrEqual':
                return [$key, '>=', $item['filter']];
            case 'lessThan':
                return [$key, '<', $item['filter']];
            case 'lessThanOrEqual':
                return [$key, '<=', $item['filter']];
            case 'inRange':
                $range = [$item['filter'], $item['filterTo']];
                $this->query->whereBetween($key, $range);
                return [];
            default:
                //logger('unknown number filter type: ' + $item['type']);
                return [];
        }
    }

    public function createDateFilter($key, $item)
    {
        switch ($item['type']) {
            case 'equals':
                return [$key, '=', $item['dateFrom']];
            case 'notEqual':
                return [$key, '!=', $item['dateFrom']];
            case 'inRange':
                $range = [Carbon::parse($item['dateFrom'])->format('Y-m-d 00:00:00'), Carbon::parse($item['dateTo'])->format('Y-m-d 23:59:59')];
                $this->query->whereBetween($key, $range);
                return [];
                break;
            default:
                logger('unknown text filter type: ' . $item['dateFrom']);
                return [];
        }
    }


    private function createSetFilter($key, $item)
    {
        $this->query->whereIn($key, $item['values']);
        return [];
    }

    public function isDoingGrouping($rowGroupCols, $groupKeys)
    {
        // we are not doing grouping if at the lowest level. we are at the lowest level
        // if we are grouping by more columns than we have keys for (that means the user
        // has not expanded a lowest level group, OR we are not grouping at all).

        return sizeof($rowGroupCols) > sizeof($groupKeys);
    }

    public function prepareCountQuery()
    {
        $builder = clone $this->query;

        if ($this->isComplexQuery($builder)) {
            $table = $this->connection->raw('('.$builder->toSql().') count_row_table');

            return $this->connection->table($table)
                ->setBindings($builder->getBindings());
        }

        $row_count = $this->wrap('row_count');
        $builder->select($this->connection->raw("'1' as {$row_count}"));
        if (! $this->keepSelectBindings) {
            $builder->setBindings([], 'select');
        }

        return $builder;
    }

    protected function isComplexQuery($builder)
    {
        return Str::contains(Str::lower($builder->toSql()), ['union', 'having', 'distinct', 'order by', 'group by']);
    }

    protected function wrap($column)
    {
        return $this->connection->getQueryGrammar()->wrap($column);
    }

    //query filter
    protected function applyFilterColumn($query, $columnName, $keyword, $boolean = 'and')
    {
        $query    = $this->getBaseQueryBuilder($query);
        $callback = $this->filter_columns[$columnName]['method'];

        if ($this->query instanceof Builder) {
            $builder = $this->query->newModelInstance()->newQuery();
        } else {
            $builder = $this->query->newQuery();
        }

        $callback($builder, $keyword);

        $query->addNestedWhereQuery($this->getBaseQueryBuilder($builder), $boolean);
    }

    protected function getBaseQueryBuilder($instance = null)
    {
        if (! $instance) {
            $instance = $this->query;
        }

        if ($instance instanceof Builder) {
            return $instance->getQuery();
        }

        return $instance;
    }


    public function getFilterValue($filter_name){
        return isset($this->request->filterModel[$filter_name])?$this->request->filterModel[$filter_name]:[];
    }

    public function customDateRangeSearch($filter_name,$model){
        $item = $this->getFilterValue($filter_name);
        if(!empty($item)){
            $range = [Carbon::parse($item['dateFrom'])->format('Y-m-d 00:00:00'), Carbon::parse($item['dateTo'])->format('Y-m-d 23:59:59')];
            return $model->whereBetween($filter_name, $range);
        }
        return $model;
    }


    public function queryGeneratedTime()
    {
        $this->query_generated_time = time() ;
    }

    public function latestQueryOnly($column_name)
    {
        $this->latest_query_column_name = $column_name ;
        return $this ;
    }

    public function latestQueryDataFilter()
    {
        if(!empty($this->latest_query_column_name)){
            if(isset($this->request->lastUpdatedAt)){
                $latest_updated_time = Carbon::createFromTimestamp($this->request->lastUpdatedAt) ;
                $this->query->where($this->latest_query_column_name,'>',$latest_updated_time);

            }
        }
    }
}
