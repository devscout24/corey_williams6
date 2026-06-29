<?php

namespace App\Http\Controllers;

use App\Models\PhpposPerson;
use App\Models\PhpposSupplier;
use App\Models\PhpposSupplierTax;
use App\Models\PhpposTaxClass;
use App\Models\PhpposInvoiceTerm;
use App\Models\PhpposAppFile;
use App\Models\PhpposPeopleFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Facades\Response;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $employee_id = auth('employee')->id();
        $search      = $request->input('search');

        $all_columns = [
            'person_id' => ['label' => 'ID', 'sort' => true],
            'company_name' => ['label' => 'Company', 'sort' => true],
            'contact_name' => ['label' => 'Contact Name', 'sort' => true],
            'phone_number' => ['label' => 'Phone', 'sort' => true],
            'fax_number' => ['label' => 'Fax', 'sort' => true],
            'email' => ['label' => 'Email', 'sort' => true],
            'balance' => ['label' => 'Balance', 'sort' => true],
            'account_number' => ['label' => 'Account #', 'sort' => true],
        ];

        $default_columns = ['company_name', 'contact_name',  'phone_number', 'fax_number', 'email'];

        $column_prefs_val = DB::table('phppos_employees_app_config')
            ->where('employee_id', $employee_id)
            ->where('key', 'suppliers_column_prefs')
            ->value('value');

        $column_order_val = DB::table('phppos_employees_app_config')
            ->where('employee_id', $employee_id)
            ->where('key', 'suppliers_column_order')
            ->value('value');

        if ($column_prefs_val) {
            $selected_columns = explode(',', $column_prefs_val);
            $selected_columns = array_values(array_intersect($selected_columns, array_keys($all_columns)));
        } else {
            $selected_columns = $default_columns;
        }

        $ordered_all_columns = [];

        if ($column_order_val) {
            $order = explode(',', $column_order_val);
            foreach ($order as $col) {
                if (isset($all_columns[$col]) && !isset($ordered_all_columns[$col])) {
                    $ordered_all_columns[$col] = $all_columns[$col];
                }
            }
        } else {
            $sourceOrder = $column_prefs_val ? $selected_columns : $default_columns;
            foreach ($sourceOrder as $col) {
                if (isset($all_columns[$col])) {
                    $ordered_all_columns[$col] = $all_columns[$col];
                }
            }
        }

        foreach ($all_columns as $col => $info) {
            if (!isset($ordered_all_columns[$col])) {
                $ordered_all_columns[$col] = $info;
            }
        }
        $all_columns = $ordered_all_columns;

        $suppliers = DB::table('phppos_suppliers as s')
            ->join('phppos_people as p', 'p.person_id', '=', 's.person_id')
            ->select('s.*', 'p.first_name', 'p.last_name', 'p.email', 'p.phone_number', 'p.fax_number')
            ->where('s.deleted', 0)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('s.company_name', 'like', "%{$search}%")
                      ->orWhere('p.first_name', 'like', "%{$search}%")
                      ->orWhere('p.last_name', 'like', "%{$search}%")
                      ->orWhere('p.email', 'like', "%{$search}%")
                      ->orWhere('p.phone_number', 'like', "%{$search}%")
                      ->orWhere('p.fax_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('s.company_name')
            ->paginate(20);

        return view('suppliers.index', compact('suppliers', 'all_columns', 'selected_columns'));
    }

    public function create(): View
    {
        return view('suppliers.form', [
            'supplier' => null,
            'person' => null,
            'tax_classes' => PhpposTaxClass::where('deleted', 0)->get(),
            'invoice_terms' => PhpposInvoiceTerm::where('deleted', 0)->get(),
            'supplier_taxes' => [],
            'files' => [],
        ]);
    }

    public function edit(int $personId): View
    {
        $supplier = PhpposSupplier::with(['taxes', 'files'])->where('person_id', $personId)->firstOrFail();
        $person = PhpposPerson::query()->where('person_id', $personId)->first();

        // Load files info
        $files = DB::table('phppos_people_files as pf')
            ->join('phppos_app_files as af', 'af.file_id', '=', 'pf.file_id')
            ->where('pf.person_id', $personId)
            ->select('af.*')
            ->get();

        return view('suppliers.form', [
            'supplier' => $supplier,
            'person' => $person,
            'tax_classes' => PhpposTaxClass::where('deleted', 0)->get(),
            'invoice_terms' => PhpposInvoiceTerm::where('deleted', 0)->get(),
            'supplier_taxes' => $supplier->taxes,
            'files' => $files,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->saveSupplier($request, null);
    }

    public function update(Request $request, int $personId): RedirectResponse
    {
        return $this->saveSupplier($request, $personId);
    }

    public function destroy(int $personId): RedirectResponse
    {
        PhpposSupplier::query()->where('person_id', $personId)->update(['deleted' => 1]);

        return redirect()->route('suppliers.index')->with('status', 'Supplier archived.');
    }

    public function downloadFile(int $fileId)
    {
        $file = PhpposAppFile::findOrFail($fileId);
        return Response::make($file->file_data, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$file->file_name.'"',
        ]);
    }

    public function deleteFile(int $fileId): RedirectResponse
    {
        PhpposPeopleFile::where('file_id', $fileId)->delete();
        PhpposAppFile::where('file_id', $fileId)->delete();

        return back()->with('status', 'File deleted.');
    }

    private function saveSupplier(Request $request, ?int $personId): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'fax_number' => ['nullable', 'string', 'max:255'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'internal_notes' => ['nullable', 'string'],
            'override_default_tax' => ['nullable', 'boolean'],
            'tax_class_id' => ['nullable', 'integer'],
            'default_term_id' => ['nullable', 'integer'],
            'balance' => ['nullable', 'numeric'],
            'tax_names' => ['nullable', 'array'],
            'tax_percents' => ['nullable', 'array'],
            'tax_cumulatives' => ['nullable', 'array'],
            'image' => ['nullable', 'image', 'max:2048'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:10240'],
        ]);

        // Custom fields
        for ($i = 1; $i <= 10; $i++) {
            $data["custom_field_{$i}_value"] = $request->input("custom_field_{$i}_value");
        }

        DB::transaction(function () use ($data, $personId, $request): void {
            $personPayload = [
                'first_name' => $data['first_name'] ?? '',
                'last_name' => $data['last_name'] ?? '',
                'email' => $data['email'] ?? '',
                'phone_number' => $data['phone_number'] ?? '',
                'fax_number' => $data['fax_number'] ?? '',
                'address_1' => $data['address_1'] ?? '',
                'address_2' => $data['address_2'] ?? '',
                'city' => $data['city'] ?? '',
                'state' => $data['state'] ?? '',
                'zip' => $data['zip'] ?? '',
                'country' => $data['country'] ?? '',
                'comments' => $data['comments'] ?? null,
            ];

            if (! $personId) {
                $personPayload['create_date'] = now();
                $person = PhpposPerson::query()->create($personPayload);
                $personId = (int) $person->person_id;
            } else {
                $personPayload['last_modified'] = now();
                PhpposPerson::query()->where('person_id', $personId)->update($personPayload);
            }

            $supplierPayload = [
                'company_name' => $data['company_name'],
                'account_number' => $data['account_number'] ?? null,
                'override_default_tax' => !empty($data['override_default_tax']) ? 1 : 0,
                'tax_class_id' => $data['tax_class_id'] ?? null,
                'default_term_id' => $data['default_term_id'] ?? null,
                'balance' => $data['balance'] ?? 0,
                'internal_notes' => $data['internal_notes'] ?? null,
                'deleted' => 0,
            ];

            for ($i = 1; $i <= 10; $i++) {
                $supplierPayload["custom_field_{$i}_value"] = $data["custom_field_{$i}_value"] ?? null;
            }

            $supplier = PhpposSupplier::query()->where('person_id', $personId)->first();

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $appFile = PhpposAppFile::create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_data' => file_get_contents($file->getRealPath()),
                    'timestamp' => now(),
                ]);
                $supplierPayload['image_id'] = $appFile->file_id;

                // Optional: Delete old image
                if ($supplier && $supplier->image_id) {
                    PhpposAppFile::where('file_id', $supplier->image_id)->delete();
                }
            }

            if (!$supplier) {
                $supplierPayload['person_id'] = $personId;
                PhpposSupplier::query()->create($supplierPayload);
            } else {
                PhpposSupplier::query()->where('person_id', $personId)->update($supplierPayload);
            }

            // Sync Taxes
            PhpposSupplierTax::where('supplier_id', $personId)->delete();
            if (!empty($data['tax_percents'])) {
                foreach ($data['tax_percents'] as $index => $percent) {
                    if (is_numeric($percent)) {
                        PhpposSupplierTax::create([
                            'supplier_id' => $personId,
                            'name' => $data['tax_names'][$index] ?? '',
                            'percent' => $percent,
                            'cumulative' => isset($data['tax_cumulatives'][$index]) ? 1 : 0,
                        ]);
                    }
                }
            }

            // Handle multiple files
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    if ($file) {
                        $appFile = PhpposAppFile::create([
                            'file_name' => $file->getClientOriginalName(),
                            'file_data' => file_get_contents($file->getRealPath()),
                            'timestamp' => now(),
                        ]);

                        PhpposPeopleFile::create([
                            'file_id' => $appFile->file_id,
                            'person_id' => $personId,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('suppliers.index')->with('status', 'Supplier saved.');
    }
}
