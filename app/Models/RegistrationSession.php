<?php

namespace App\Models;

use DB;
use App\Helpers\ModelHelper;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string   name
 * @property string   status
 * @property int      finger_page
 * @property string   device_id
 * @property string   expires_at
 * @property string   created_at
 * @property string   updated_at
 */
class RegistrationSession extends Model
{
    protected $table = 'registration_sessions';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'status',
        'finger_page',
        'device_id',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [];

    protected $casts = [
        'name'        => 'string',
        'status'      => 'string',
        'finger_page' => 'int',
        'device_id'   => 'string',
        'expires_at'  => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    protected $dates = [];

    public $timestamps = true;

    public $incrementing = true;

    // Relations ...

    public static function mapSchema($params = [], $user = [])
    {
        $model = new self;

        return [
            'field' => [
                'id'          => ['column' => $model->table.'.id',          'alias' => 'id',          'type' => 'int'],
                'name'        => ['column' => $model->table.'.name',        'alias' => 'name',        'type' => 'string'],
                'status'      => ['column' => $model->table.'.status',      'alias' => 'status',      'type' => 'string'],
                'finger_page' => ['column' => $model->table.'.finger_page', 'alias' => 'finger_page', 'type' => 'int'],
                'device_id'   => ['column' => $model->table.'.device_id',   'alias' => 'device_id',   'type' => 'string'],
                'expires_at'  => ['column' => $model->table.'.expires_at',  'alias' => 'expires_at',  'type' => 'date'],
                'created_at'  => ['column' => $model->table.'.created_at',  'alias' => 'created_at',  'type' => 'date'],
                'updated_at'  => ['column' => $model->table.'.updated_at',  'alias' => 'updated_at',  'type' => 'date'],
            ],
            'join'  => [],
            'where' => [],
        ];
    }

    public static function datatables($start, $length, $order, $dir, $search, $filter = [])
    {
        $schema = self::mapSchema();

        $totalData = self::count();

        $qry = ModelHelper::select($schema['field'], null, __CLASS__);
        ModelHelper::join($schema['join'], null, $qry);

        $totalFiltered = $qry->count();

        if (empty($search)) {

            if ($length > 0) {
                $qry->skip($start)->take($length);
            }

            foreach ($order as $row) {
                $qry->orderBy($row['column'], $row['dir']);
            }

        } else {
            foreach (array_values($schema['field']) as $key => $val) {
                if ($key < 1) {
                    $qry->whereRaw('('.$val['column'].'::varchar(255) ILIKE \'%'.$search.'%\'');
                } else if (count(array_values($schema['field'])) == ($key + 1)) {
                    $qry->orWhereRaw($val['column'].'::varchar(255) ILIKE \'%'.$search.'%\')');
                } else {
                    $qry->orWhereRaw($val['column'].'::varchar(255) ILIKE \'%'.$search.'%\'');
                }
            }

            $totalFiltered = $qry->count();

            if ($length > 0) {
                $qry->skip($start)->take($length);
            }

            foreach ($order as $row) {
                $qry->orderBy($row['column'], $row['dir']);
            }
        }

        return [
            'data'          => $qry->get(),
            'totalData'     => $totalData,
            'totalFiltered' => $totalFiltered,
        ];
    }

    public static function getPaginatedResult($params, $request)
    {
        $append = [];
        $schema = self::mapSchema();

        $paramsPage = isset($params['page']) ? $params['page'] : 0;

        $or = [];

        unset($params['page']);

        if (isset($params['or']) && $params['or']) {
            $or = $params['or'];
            unset($params['or']);
        }

        $db = ModelHelper::select($schema['field'], $request, __CLASS__);
        ModelHelper::join($schema['join'], $request, $db);

        if ($params) {
            ModelHelper::dynamicFilterAnd($params, $request, $db, __CLASS__);
        }

        if ($or) {
            ModelHelper::dynamicFilterOr($or, $request, $db, __CLASS__);
        }

        $results = ModelHelper::generatePagingResults($schema, $paramsPage, $params, $request, $db, $append);

        return response()->json($results);
    }

    public static function getById($id, $params = [], $request = null)
    {
        $models = new self;

        $schema = self::mapSchema();

        $db = ModelHelper::select($schema['field'], $request, __CLASS__)->where($models->table.'.id', $id);

        ModelHelper::join($schema['join'], $request, $db);

        return response()->json($db->first());
    }

    public static function getAllResult($params, $request)
    {
        $append = [];
        $schema = self::mapSchema();

        $or = [];

        unset($params['all']);

        if (isset($params['or']) && $params['or']) {
            $or = $params['or'];
            unset($params['or']);
        }

        $db = ModelHelper::select($schema['field'], $request, __CLASS__);
        ModelHelper::join($schema['join'], $request, $db);

        if ($params) {
            ModelHelper::dynamicFilterAnd($params, $request, $db, __CLASS__);
        }

        if ($or) {
            ModelHelper::dynamicFilterOr($or, $request, $db, __CLASS__);
        }

        $results = ModelHelper::generateAllResults($schema, $params, $request, $db, $append);

        return response()->json($results);
    }

    public static function createOrUpdate($params, $method, $request)
    {
        DB::beginTransaction();

        if (isset($params['_token']) && $params['_token']) {
            unset($params['_token']);
        }

        if (isset($params['id']) && $params['id']) {
            $old = self::getById($params['id'])->original;

            self::where('id', $params['id'])->update($params);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Succesfully Updated Data',
                'data'    => self::getById($params['id'])->original,
            ]);
        }

        $save = self::create($params);

        DB::commit();

        return response()->json([
            'status'  => 'success',
            'message' => 'Succesfully Added Data',
            'data'    => self::getById($save->id)->original,
        ]);
    }

    public static function deleteById($id, $params, $request)
    {
        self::where('id', $id)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Succesfully Deleted Data',
        ]);
    }

    public static function approveById($id, $params, $request)
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Succesfully Approved Data',
            'data'    => null,
        ]);
    }
}
