<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $expenses = Expense::where('company_id', $user->company_id)
                ->with('employee.user')
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json($expenses);
        } else {
            $expenses = Expense::where('employee_id', $user->employee->id ?? 0)
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json($expenses);
        }
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'employee') {
            return response()->json(['message' => 'Only employees can submit expenses'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'date_incurred' => 'required|date',
            'category' => 'required|string',
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120', // Max 5MB
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            // Save to storage/app/public/receipts
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        $expense = Expense::create([
            'employee_id' => $user->employee->id,
            'company_id' => $user->company_id,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'date_incurred' => $request->date_incurred,
            'category' => $request->category,
            'receipt_path' => $receiptPath,
            'status' => 'pending',
        ]);

        return response()->json($expense, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string',
        ]);

        $expense = Expense::where('company_id', $user->company_id)->findOrFail($id);
        
        if ($expense->status === 'paid') {
            return response()->json(['message' => 'Cannot change status of a paid expense'], 400);
        }

        $expense->update([
            'status' => $request->status,
            'admin_note' => $request->admin_note,
        ]);

        return response()->json($expense);
    }

    public function markAsPaid(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $expense = Expense::where('company_id', $user->company_id)->findOrFail($id);
        
        if ($expense->status !== 'approved') {
            return response()->json(['message' => 'Only approved expenses can be marked as paid'], 400);
        }

        $expense->update([
            'status' => 'paid',
        ]);

        return response()->json($expense);
    }
}
