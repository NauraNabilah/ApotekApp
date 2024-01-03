<?php

namespace App\Http\Controllers;

use PDF;
use Excel;

use App\Models\Order;
use App\Models\Medicine;
// use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::query()->with('user');

        $date = $request->input('filter');

        if($date) {
            $query->whereDate('created_at', '=', $date);
        }

        $orders = $query->simplePaginate(10);
        return view('order.kasir.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $medicines = Medicine::all();
        return view("order.kasir.create", compact('medicines'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate(
            [
                'name_customer' => 'required',
                'medicines' => 'required',
            ],
            [
                'name_customer.required' => 'Nama Pembeli Harus Diisi',
                'medicines.required' => 'Obat Harus Diisi',

            ]
        );

        $arrayDistinct = array_count_values($request->medicines);

        $arrayAssocMedicines = [];

        foreach ($arrayDistinct as $id => $count) {
            $medicine = Medicine::where('id', $id)->first();
            $subPrice = $medicine->price * $count;
            $arrayItem = [
                'id' => $id,
                'name_medicine' => $medicine->name,
                'qty' => $count,
                'price' => $medicine->price,
                'sub_price' => $subPrice,
            ];

            array_push($arrayAssocMedicines, $arrayItem);
        }

        $totalPrice = 0;

        foreach ($arrayAssocMedicines as $item) {
            $totalPrice += (int)$item['sub_price'];
        }

        $priceWithPPN = $totalPrice + ($totalPrice * 0.01);

        $proses = Order::create ([
            'user_id' => Auth::user()->id,
            'medicines' => $arrayAssocMedicines,
            'name_customer' => $request->name_customer,
            'total_price' => $priceWithPPN,
        ]);

        if ($proses) {
            $order = Order::where('user_id', Auth::user()->id)->orderBy('created_at', 'DESC')->first();
            return redirect()->route('kasir.order.print', $order['id']);
        }else {
            return redirect()->back()->with('failed', 'Gagal membuat data pembelian, silahkan coba kembali dengand ata yang sesuai');
        }


    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $order = Order::find($id);
        return view('order.kasir.print', compact('order'));
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }

    public function downloadPDF($id)
    {
        $order = Order::find($id)->toArray();
        view()->share('order', $order);
        $pdf = PDF::loadView('order.kasir.download-pdf', $order);
        return $pdf->download('receipt.pdf');

    }

    public function data()
    {
        $orders = Order::with('user')->simplePaginate(5);
        return view("order.admin.index", compact('orders'));

    }

    public function exportExcel() 
    {
        $fileName = 'data_pembelian.xlsx';
        return Excel::download(new OrdersExport, $fileName);
    }
}
