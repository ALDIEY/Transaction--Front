<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Compte;
use App\Models\transaction;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function depot(Request $request) {
        $montant=$request->input('montant');
        $numero=$request->input('numero');
        $numeroClie=Client::where('clients.numero',$numero)->first()->id;

        $fournisseur = Compte::where('comptes.client_id', $numeroClie)->first()->typeCompte;
        $montantMinimum = 0;
    
        switch ($fournisseur) {
            case 'Orange Money':
            case 'Wave':
                $montantMinimum = 500;
                break;
            case 'Wari':
                $montantMinimum = 1000;
                break;
            case 'CB':
                $montantMinimum = 10000;
                break;
        }
    
        if ($montant < $montantMinimum) {
            return response()->json(['message' =>
             'Le montant est inférieur au montant minimum autorisé pour ce fournisseur.'], 422);
        }
    
        $montantMaximum = 1000000;
        if ($montant > $montantMaximum) {
            return response()->json(['message' => 'Le montant dépasse le montant maximum autorisé.'], 422);
        }
    
 try {
    DB::beginTransaction();

    $numeroClient=Client::where('clients.numero',$numero)->first()->id;
$compte=Compte::where('comptes.client_id',$numeroClient)->first();
$compteId=$compte->id;
$compte->increment('solde',$montant);
DB::commit();
 } catch (\Throwable $th) {
    
    DB::rollBack();
    throw $th;

   }
   $transaction=transaction::create([
    'client_id'=> $numeroClient,
    'compte_id'=>$compteId,
    'montant'=>$montant,
    'statut'=>'depot',
    'frait'=>0
    ]);
   return response()->json($transaction);
    }



    //retrait
    public function retrait(Request $request)
   {
    $montant=$request->input('montant');
    $numero=$request->input('numero');
   
       $client = Client::where('clients.numero', $numero)->first();
    $typeCompte = Compte::where('client_id',$client->id)->first()->typeCompte;

    $compte = Compte::where('client_id', $client->id)->first();

    // if ($typeCompte !== 'Wari' && !$client->comptes->contains('typeCompte', $typeCompte)) {
    //     return response()->json(['message' => 'Vous n\'avez pas de compte du même type chez ce fournisseur.'], 422);
    // }
    $montantMinimum = 0;
    switch ($typeCompte)
    {
        case 'Orange Money':
        case 'Wave':
            $montantMinimum = 500;
            break;
        case 'Wari':
            $montantMinimum = 1000;
            break;
        case 'CB':
            $montantMinimum = 10000;
            break;
    }
    if ($montant < $montantMinimum)
    {
        return response()->json(['message' =>
        'Le montant est inférieur au montant minimum autorisé pour ce fournisseur.'], 422);
    }


    if ($compte->solde < $montant)
    {
        return response()->json(['message' => 'Solde insuffisant pour effectuer ce retrait.'], 422);
    }

    $codeRetrait = null;
    if ($typeCompte !== 'Wari')
    {
        $codeRetrait =random_int(pow(10,9-1), pow(10,10)-1);
    }

    $codeRetraitImmediat = null;
    $dateLimiteRetraitImmediat = null;
    if ($typeCompte === 'CB')
    {
        $codeRetraitImmediat = random_int(pow(10,9-1), pow(10,10)-1);
        $dateLimiteRetraitImmediat = now()->addHours(24);
    }
    
    $compte->decrement('solde', $montant);
    

    $transaction = Transaction::create([
        'client_id' => $client->id,
        'compte_id' => $compte->id,
        'montant' => $montant,
        'statut' => 'retrait',
        'code_retrait' => $codeRetrait,
        'code_retrait_immediat' => $codeRetraitImmediat,
        'date_limite_retrait_immediat' => $dateLimiteRetraitImmediat,
    ]);

    return response()->json($transaction);
   }


 }
