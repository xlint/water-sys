<?php

namespace App\Http\Controllers;

use App\Billing;
use App\MeterReading;
use App\Payment;
use App\Client;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    /**
     *Create a new Billing Controller and authenticate all its requests
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     *Display all the unpaid bills
     * @return \Response
     */
    public function index()
    {
        $bills = \DB::table('billings')->where('balance', '>', 0)
            ->orderBy('Balance')
            ->get();
        return view('bills.index', ['bills' => $bills]);
    }

    /**
     *Render the form to record a new bill
     * @return \Response
     */
    public function create()
    {
        $readings = MeterReading::all();
        return view('bills.create', ['readings' => $readings]);
    }

    /**
     *Store the recorded bill
     * @param Request $request
     * @return \Response
     */
    public function store(Request $request)
    {
        //TODO generate a random alphanumeric
        $this->validate($request, [
            'meter_reading_id' => 'required|numeric',
            'price' => 'required|numeric',
            'deadline' => 'required'
        ]);
        $meter_reading = MeterReading::find($request['meter_reading_id']);
        $amount = $request['price'] * $meter_reading->reading;
        $number = 'HHGVG665';
        $bill = Billing::create([
            'meter_reading_id' => $request['meter_reading_id'],
            'number' => $number,
            'amount' => $amount,
            'deadline' => $request['deadline'],
            'balance' => $amount
        ]);
        return redirect('/bills/'.$bill->id);
    }

    /**
     * Retrieve a certain bill
     * @param Billing $bill
     * @return \Response
     */
    public function bill(Billing $bill)
    {
        return view('bills.bill', ['bill' => $bill]);
    }

    /**
     *Render a form to update a certain bill
     * @param Billing $bill
     * @return \Response
     */
    public function update(Billing $bill)
    {
        $readings = MeterReading::all();
        return view('bills.update', ['bill' => $bill,'readings' => $readings]);
    }

    /**Store an updated bill
     * @param Request $request
     * @return \Response
     */
    public function store_updated(Request $request)
    {
        $this->validate($request, [
            'meter_reading_id' => 'required|numeric',
            'price' => 'required|numeric',
            'deadline' => 'required'
        ]);
        $meter_reading = MeterReading::find($request['meter_reading_id']);
        $amount = $request['price'] * $meter_reading->reading;
        $bill = Billing::find($request['id']);
        $payments = Payment::where('billing_id', $bill->id);
        $sum = 0;
        if($payments) {
            foreach ($payments as $payment) {
                $sum += $payment->amount;
            }
        }
        $balance = $amount - $sum;
        $bill->meter_reading_id = $request['meter_reading_id'];
        $bill->amount = $amount;
        $bill->deadline = $request['deadline'];
        $bill->balance = $balance;
        $bill->save();
        return redirect('bills/' . $bill->id);
    }
    /**
     * Return bills for a certain meter number
     *@param Request $request
     *@return \Response
     */
    public function bills_by_meter_number(Request $request)
    {
        $meter_reading = $request['meter_reading'];
        $client = Client::find($meter_reading);
        return view('meteter_readings.index');
    }
}
