<?php

// expense_tracker.php

define('EXPENSES_FILE', 'expenses.json');

date_default_timezone_set('Asia/Jakarta');

function init_storage()
{
    if (!file_exists(EXPENSES_FILE)) {
        file_put_contents(EXPENSES_FILE, json_encode([]));
    }
}

function load_expenses()
{
    return json_decode(file_get_contents(EXPENSES_FILE), true);
}

function save_expenses($expenses)
{
    file_put_contents(EXPENSES_FILE, json_encode($expenses, JSON_PRETTY_PRINT));
}

function add_expense($description, $amount)
{
    $expenses = load_expenses();
    $new_id = empty($expenses) ? 1 : max(array_column($expenses, 'id')) + 1;
    $expense = [
        'id' => $new_id,
        'date' => date('Y-m-d'),
        'description' => $description,
        'amount' => (float)$amount
    ];
    $expenses[] = $expense;
    save_expenses($expenses);
    echo "# Expense added successfully (ID: {$new_id})\n";
}

function list_expenses()
{
    $expenses = load_expenses();
    echo "# ID   Date        Description                     Amount\n";
    foreach ($expenses as $e) {
        printf(
            "# %-4d %-10s  %-30s Rp %-10s\n",
            $e['id'],
            $e['date'],
            $e['description'],
            number_format($e['amount'], 0, ',', '.')
        );
    }
}


function delete_expense($id)
{
    $expenses = load_expenses();
    $expenses = array_filter($expenses, fn($e) => $e['id'] != $id);
    save_expenses(array_values($expenses));
    echo "# Expense deleted successfully\n";
}

function update_expense($id, $description, $amount)
{
    $expenses = load_expenses();
    foreach ($expenses as &$e) {
        if ($e['id'] == $id) {
            if ($description !== null) $e['description'] = $description;
            if ($amount !== null) $e['amount'] = (float)$amount;
            save_expenses($expenses);
            echo "# Expense updated successfully\n";
            return;
        }
    }
    echo "# Expense ID not found\n";
}

function summary($month = null)
{
    $expenses = load_expenses();
    $total = 0;
    foreach ($expenses as $e) {
        $expense_month = date('n', strtotime($e['date']));
        $expense_year = date('Y', strtotime($e['date']));
        if ($month === null || ($expense_month == $month && $expense_year == date('Y'))) {
            $total += $e['amount'];
        }
    }
    if ($month !== null) {
        echo "# Total expenses for " . date('F', mktime(0, 0, 0, $month, 1)) . ": \$" . number_format($total, 2) . "\n";
    } else {
        echo "# Total expenses: \$" . number_format($total, 2) . "\n";
    }
}

// CLI Handling
init_storage();

$options = getopt("", [
    "command:",
    "description::",
    "amount::",
    "id::",
    "month::"
]);

$command = $options['command'] ?? null;

switch ($command) {
    case 'add':
        if (!isset($options['description'], $options['amount']) || $options['amount'] < 0) {
            echo "# Error: Please provide a valid description and a positive amount\n";
        } else {
            add_expense($options['description'], $options['amount']);
        }
        break;
    case 'list':
        list_expenses();
        break;
    case 'delete':
        if (isset($options['id'])) {
            delete_expense($options['id']);
        } else {
            echo "# Error: Please provide an expense ID\n";
        }
        break;
    case 'update':
        if (isset($options['id'])) {
            $desc = $options['description'] ?? null;
            $amt = $options['amount'] ?? null;
            update_expense($options['id'], $desc, $amt);
        } else {
            echo "# Error: Please provide an expense ID\n";
        }
        break;
    case 'summary':
        summary($options['month'] ?? null);
        break;
    default:
        echo "Usage: php expense_tracker.php --command=[add|list|delete|update|summary] [--description=...] [--amount=...] [--id=...] [--month=...]\n";
}
