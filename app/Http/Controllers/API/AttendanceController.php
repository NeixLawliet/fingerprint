<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function get(Request $request, $id = null)
    {
        $params = $request->all();

        if ($id != null) {
            $res = AttendanceLog::getById($id, $params, $request);
        } else if (isset($params['all']) && $params['all']) {
            $res = AttendanceLog::getAllResult($params, $request);
        } else {
            $res = AttendanceLog::getPaginatedResult($params, $request);
        }

        return $res;
    }

    public function post(Request $request)
    {
        $params = $request->all();
        return AttendanceLog::createOrUpdate($params, $request->method(), $request);
    }

    public function put(Request $request, $id)
    {
        $params = $request->all();
        $params['id'] = $id;
        return AttendanceLog::createOrUpdate($params, $request->method(), $request);
    }

    public function patch(Request $request, $id)
    {
        $params = $request->all();
        $params['id'] = $id;
        return AttendanceLog::createOrUpdate($params, $request->method(), $request);
    }

    public function delete(Request $request, $id)
    {
        $params = $request->all();
        return AttendanceLog::deleteById($id, $params, $request);
    }

    public function approve(Request $request, $id)
    {
        $params = $request->all();
        return AttendanceLog::approveById($id, $params, $request);
    }

    public function datatables(Request $request)
    {
        $columns = [
            'attendance_logs.id',
        ];

        $data_order = [];
        $limit  = $request->length;
        $start  = $request->start;

        foreach ($request->order as $row) {
            $nested_order['column'] = $columns[$row['column']];
            $nested_order['dir']    = $row['dir'];
            $data_order[]           = $nested_order;
        }

        $order  = $data_order;
        $dir    = $request->order[0]['dir'];
        $search = $request->search['value'];
        $filter = $request->filter;

        $res = AttendanceLog::datatables($start, $limit, $order, $dir, $search, $filter);

        $data = [];

        if (!empty($res['data'])) {
            foreach ($res['data'] as $row) {
                $nested_data = $row;

                $nested_data['action']  = '<div class="dropdown text-end">';
                $nested_data['action'] .= '  <button type="button" class="btn btn-dark btn-sm px-2 py-1 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">';
                $nested_data['action'] .= '      <i class="fas fa-ellipsis-h"></i>';
                $nested_data['action'] .= '  </button>';
                $nested_data['action'] .= '  <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated">';
                $nested_data['action'] .= '      <a href="javascript:void(0);" class="dropdown-item text-danger" id="delete-data" data-id="'.$row['id'].'">';
                $nested_data['action'] .= '          <i class="fas fa-trash me-2"></i> Delete';
                $nested_data['action'] .= '      </a>';
                $nested_data['action'] .= '  </div>';
                $nested_data['action'] .= '</div>';

                $data[] = $nested_data;
            }
        }

        return response()->json([
            'draw'            => intval($request->draw),
            'recordsTotal'    => intval($res['totalData']),
            'recordsFiltered' => intval($res['totalFiltered']),
            'data'            => $data,
            'order'           => $order,
        ]);
    }
}
