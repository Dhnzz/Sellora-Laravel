<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class RoleController
{
    public function index(Request $request)
    {
        $data = [
            'title' => 'Manajemen Role',
            'role' => Auth::user()->getRoleNames()->first(),
            'active' => 'user_management_role',
            'breadcrumbs' => [
                [
                    'name' => 'Manajemen User',
                    'link' => '#',
                ],
                [
                    'name' => 'Role',
                    'link' => route('owner.user_management.role.index'),
                ],
            ],
        ];
        // Untuk tampilan awal, $roles sudah di-paginate di atas
        return view('owner.user_management.role.index', compact('data'));
    }

    public function getAll(Request $request)
    {
        if ($request->ajax()) {
            $data = User::leftJoin('admins', 'users.id', '=', 'admins.user_id')
                ->leftJoin('warehouse_managers', 'users.id', '=', 'warehouse_managers.user_id')
                ->leftJoin('sales_agents', 'users.id', '=', 'sales_agents.user_id')
                ->leftJoin('customers', 'users.id', '=', 'customers.user_id')
                ->leftJoin('model_has_roles', function ($join) {
                    $join->on('users.id', '=', 'model_has_roles.model_id')->where('model_has_roles.model_type', '=', 'App\Models\User');
                })
                ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select(
                    'users.id',
                    'users.email',
                    \DB::raw('
                        COALESCE(admins.name, warehouse_managers.name, sales_agents.name, customers.name) as name
                    '),
                    \DB::raw('
                        COALESCE(admins.id, warehouse_managers.id, sales_agents.id, customers.id) as role_id
                    '),
                    \DB::raw('
                        COALESCE(admins.phone, warehouse_managers.phone, sales_agents.phone, customers.phone) as phone
                    '),
                    'roles.name as role',
                    'users.created_at',
                )
                ->where('roles.name', '!=', 'owner')
                ->latest('users.created_at');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i:s');
                })
                ->addColumn('options', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-1">';
                    $btn .= '<a href="' . route('owner.user_management.'. ($row->role != 'warehouse' ? $row->role : 'warehouse_manager') .'.detail', $row->role_id) . '" class="btn btn-sm btn-primary"><i class="ti ti-eye"></i></a>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->addColumn('role', function ($row) {
                    $userModel = User::find($row->id);
                    return $userModel ? ucfirst($userModel->getRoleNames()->first()) : null;
                })
                ->rawColumns(['options'])
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && !empty($request->input('search')['value'])) {
                        $searchValue = $request->input('search')['value'];
                        $query->having('name', 'like', "%{$searchValue}%")
                        ->orHaving('phone', 'like', "%{$searchValue}%")
                        ->orHaving('role', 'like', "%{$searchValue}%");
                    }
                })
                ->order(function ($query) use ($request) {
                    // Sortir berdasarkan kolom yang diminta DataTables
                    if ($request->has('order')) {
                        $order = $request->input('order')[0];
                        $columnIndex = $order['column'];
                        $columnName = $request->input('columns')[$columnIndex]['data'];
                        $sortDirection = $order['dir'];

                        // Pastikan hanya kolom yang boleh di-sort yang diproses
                        $sortableColumns = ['id', 'name', 'email', 'phone', 'role', 'created_at']; // Kolom yang bisa di-sort
                        if (in_array($columnName, $sortableColumns)) {
                            $query->orderBy($columnName, $sortDirection);
                        } elseif ($columnName === 'DT_RowIndex') {
                            // Abaikan sorting untuk kolom nomor urut jika tidak diperlukan
                            // Atau tambahkan default sorting jika perlu
                            $query->orderBy('created_at', 'desc');
                        }
                    }
                })
                ->make(true);
        }
    }
}
