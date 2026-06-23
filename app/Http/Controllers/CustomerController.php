<?php

namespace App\Http\Controllers;

use App\Models\PhpposAppFile;
use App\Models\PhpposCustomer;
use App\Models\PhpposCustomerTax;
use App\Models\PhpposPeopleFile;
use App\Models\PhpposPerson;
use App\Models\PhpposPriceTier;
use App\Models\PhpposStoreAccount;
use App\Models\PhpposTaxClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends Controller
{
    public function index(): View
    {
        $employee_id = auth('employee')->id();

        $column_prefs_val = DB::table('phppos_employees_app_config')
            ->where('employee_id', $employee_id)
            ->where('key', 'customers_column_prefs')
            ->value('value');

        $all_columns = [
            'person_id' => ['label' => 'ID', 'sort' => true],
            'full_name' => ['label' => 'Name', 'sort' => true],
            'company_name' => ['label' => 'Company', 'sort' => true],
            'email' => ['label' => 'Email', 'sort' => true],
            'phone_number' => ['label' => 'Phone', 'sort' => true],
            'balance' => ['label' => 'Balance', 'sort' => true],
            'credit_limit' => ['label' => 'Credit Limit', 'sort' => true],
            'points' => ['label' => 'Points', 'sort' => true],
            'taxable' => ['label' => 'Taxable', 'sort' => true],
        ];

        $default_columns = ['full_name', 'company_name', 'email', 'phone_number', 'balance'];

        if ($column_prefs_val) {
            $selected_columns = explode(',', $column_prefs_val);
            $selected_columns = array_values(array_intersect($selected_columns, array_keys($all_columns)));

            $ordered_all_columns = [];
            foreach ($selected_columns as $col) {
                if (isset($all_columns[$col])) {
                    $ordered_all_columns[$col] = $all_columns[$col];
                }
            }
            foreach ($all_columns as $col => $info) {
                if (!isset($ordered_all_columns[$col])) {
                    $ordered_all_columns[$col] = $info;
                }
            }
            $all_columns = $ordered_all_columns;
        } else {
            $selected_columns = $default_columns;

            $ordered_all_columns = [];
            foreach ($default_columns as $col) {
                if (isset($all_columns[$col])) {
                    $ordered_all_columns[$col] = $all_columns[$col];
                }
            }
            foreach ($all_columns as $col => $info) {
                if (!isset($ordered_all_columns[$col])) {
                    $ordered_all_columns[$col] = $info;
                }
            }
            $all_columns = $ordered_all_columns;
        }

        $customers = PhpposCustomer::query()
            ->join('phppos_people', 'phppos_people.person_id', '=', 'phppos_customers.person_id')
            ->select('phppos_people.*', 'phppos_customers.*')
            ->where('phppos_customers.deleted', 0)
            ->orderBy('phppos_people.last_name', 'asc')
            ->paginate(20);

        return view('customers.index', compact('customers', 'all_columns', 'selected_columns'));
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function edit(int $personId): View
    {
        return $this->form($personId);
    }

    private function form(?int $personId): View
    {
        $customer = $personId ? PhpposCustomer::with(['person', 'taxes', 'tier', 'location'])->findOrFail($personId) : null;
        $tiers = PhpposPriceTier::where('deleted', 0)->orderBy('name')->get();
        $taxClasses = PhpposTaxClass::orderBy('name')->get();
        
        $customerFiles = $personId ? DB::table('phppos_people_files')
            ->join('phppos_app_files', 'phppos_app_files.file_id', '=', 'phppos_people_files.file_id')
            ->where('phppos_people_files.person_id', $personId)
            ->get() : [];

        return view('customers.form', [
            'customer' => $customer,
            'tiers' => $tiers,
            'taxClasses' => $taxClasses,
            'customerFiles' => $customerFiles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->saveCustomer($request, null);
    }

    public function update(Request $request, int $personId): RedirectResponse
    {
        return $this->saveCustomer($request, $personId);
    }

    public function destroy(int $personId): RedirectResponse
    {
        PhpposCustomer::query()->where('person_id', $personId)->update(['deleted' => 1]);
        return redirect()->route('customers.index')->with('status', 'Customer archived.');
    }

    private function saveCustomer(Request $request, ?int $personId): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'tier_id' => ['nullable', 'integer'],
            'balance' => ['nullable', 'numeric'],
            'credit_limit' => ['nullable', 'numeric'],
            'points' => ['nullable', 'integer'],
            'taxable' => ['nullable', 'boolean'],
            'tax_certificate' => ['nullable', 'string', 'max:255'],
            'override_default_tax' => ['nullable', 'boolean'],
            'tax_class_id' => ['nullable', 'integer'],
            'tax_names' => ['nullable', 'array'],
            'tax_percents' => ['nullable', 'array'],
            'tax_cumulatives' => ['nullable', 'array'],
            'customer_files' => ['nullable', 'array'],
            'customer_files.*' => ['file', 'max:10240'], // 10MB
        ]);

        // Custom fields
        for ($i = 1; $i <= 10; $i++) {
            $data["custom_field_{$i}_value"] = $request->input("custom_field_{$i}_value");
        }

        DB::transaction(function () use ($data, $personId, $request): void {
            $personPayload = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'full_name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'] ?? '',
                'phone_number' => $data['phone_number'] ?? '',
                'address_1' => $data['address_1'] ?? '',
                'address_2' => $data['address_2'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'zip' => $data['zip'] ?? '',
                'country' => $data['country'] ?? '',
                'comments' => $data['comments'] ?? null,
                'last_modified' => now(),
            ];

            if ($personId) {
                PhpposPerson::query()->where('person_id', $personId)->update($personPayload);
            } else {
                $personPayload['create_date'] = now();
                $person = PhpposPerson::query()->create($personPayload);
                $personId = (int) $person->person_id;
            }

            $customer = PhpposCustomer::find($personId);
            $oldBalance = $customer ? $customer->balance : 0;
            $newBalance = $data['balance'] ?? 0;

            $customerPayload = [
                'person_id' => $personId,
                'company_name' => $data['company_name'] ?? '',
                'account_number' => $data['account_number'] ?? null,
                'tier_id' => ($data['tier_id'] ?? -1) == -1 ? null : $data['tier_id'],
                'balance' => $newBalance,
                'credit_limit' => $data['credit_limit'] ?? null,
                'points' => $data['points'] ?? 0,
                'taxable' => !empty($data['taxable']) ? 1 : 0,
                'tax_certificate' => $data['tax_certificate'] ?? null,
                'override_default_tax' => !empty($data['override_default_tax']) ? 1 : 0,
                'tax_class_id' => $data['tax_class_id'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'deleted' => 0,
            ];

            for ($i = 1; $i <= 10; $i++) {
                $customerPayload["custom_field_{$i}_value"] = $data["custom_field_{$i}_value"] ?? null;
            }

            if ($customer) {
                $customer->update($customerPayload);
            } else {
                PhpposCustomer::create($customerPayload);
            }

            // Store Account Transaction for balance change
            if ($newBalance != $oldBalance) {
                PhpposStoreAccount::create([
                    'customer_id' => $personId,
                    'transaction_amount' => $newBalance - $oldBalance,
                    'balance' => $newBalance,
                    'comment' => 'Manual edit of balance',
                    'date' => now(),
                ]);
            }

            // Sync Taxes
            PhpposCustomerTax::where('customer_id', $personId)->delete();
            if (!empty($data['tax_percents'])) {
                foreach ($data['tax_percents'] as $index => $percent) {
                    if (is_numeric($percent)) {
                        PhpposCustomerTax::create([
                            'customer_id' => $personId,
                            'name' => $data['tax_names'][$index] ?? '',
                            'percent' => $percent,
                            'cumulative' => isset($data['tax_cumulatives'][$index]) ? 1 : 0,
                        ]);
                    }
                }
            }

            // Handle File Uploads
            if ($request->hasFile('customer_files')) {
                foreach ($request->file('customer_files') as $file) {
                    $appFile = PhpposAppFile::create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_data' => file_get_contents($file->getRealPath()),
                        'timestamp' => now(),
                    ]);

                    PhpposPeopleFile::create([
                        'person_id' => $personId,
                        'file_id' => $appFile->file_id,
                    ]);
                }
            }
        });

        return redirect()->route('customers.index')->with('status', 'Customer saved.');
    }

    public function downloadFile(int $fileId): BinaryFileResponse|RedirectResponse
    {
        $file = PhpposAppFile::find($fileId);
        if (!$file) {
            return redirect()->back()->with('error', 'File not found.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'pos_file');
        file_put_contents($tempPath, $file->file_data);

        return Response::download($tempPath, $file->file_name)->deleteFileAfterSend(true);
    }

    public function deleteFile(int $fileId): RedirectResponse
    {
        PhpposPeopleFile::where('file_id', $fileId)->delete();
        PhpposAppFile::where('file_id', $fileId)->delete();
        return redirect()->back()->with('status', 'File deleted.');
    }
}
