<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Agent::query();

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('agent_type', function ($row) {
                    return '<span class="badge ' . ($row->agent_type === Agent::TYPE_IN_PARTNER ? 'bg-primary' : 'bg-secondary') . '">' . $row->agent_type_label . '</span>';
                })
                ->editColumn('status', function ($row) {
                    return $row->status
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    $editUrl = route('agents.edit', $row->id);
                    $deleteUrl = route('agents.destroy', $row->id);
                    $csrf = csrf_field();
                    $method = method_field('DELETE');

                    return "
                        <div class='d-flex'>
                            <a href='{$editUrl}' class='btn btn-sm btn-primary me-1'><i class='fas fa-edit'></i></a>
                            <form action='{$deleteUrl}' method='POST' onsubmit=\"return confirm('Delete this agent? Their booking history will be kept.');\">
                                {$csrf}
                                {$method}
                                <button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>
                            </form>
                        </div>
                    ";
                })
                ->rawColumns(['agent_type', 'status', 'action'])
                ->make(true);
        }

        return view('agents.index');
    }

    public function create()
    {
        return view('agents.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:agents,phone',
            'agent_type' => 'required|in:' . Agent::TYPE_IN_PARTNER . ',' . Agent::TYPE_OUT_PARTNER,
            'status' => 'nullable|boolean',
            'remarks' => 'nullable|string',
        ]);

        $validated['status'] = $request->boolean('status');
        $validated['created_by'] = auth()->id();

        Agent::create($validated);

        return redirect()->route('agents.index')->with('success', 'Agent added successfully!');
    }

    public function edit($id)
    {
        $agent = Agent::findOrFail($id);
        return view('agents.edit', compact('agent'));
    }

    public function update(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:agents,phone,' . $agent->id,
            'agent_type' => 'required|in:' . Agent::TYPE_IN_PARTNER . ',' . Agent::TYPE_OUT_PARTNER,
            'status' => 'nullable|boolean',
            'remarks' => 'nullable|string',
        ]);

        $validated['status'] = $request->boolean('status');

        $agent->update($validated);

        return redirect()->route('agents.index')->with('success', 'Agent updated successfully!');
    }

    public function destroy($id)
    {
        $agent = Agent::findOrFail($id);
        $agent->delete();
        return redirect()->route('agents.index')->with('success', 'Agent deleted successfully!');
    }
}