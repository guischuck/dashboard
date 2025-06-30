<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectionAttempt;
use Illuminate\Http\Request;

class CollectionAttemptController extends Controller
{
    public function index($employmentRelationshipId)
    {
        $attempts = CollectionAttempt::where('employment_relationship_id', $employmentRelationshipId)
            ->orderBy('tentativa_num')
            ->get();
        
        return response()->json($attempts);
    }

    public function update(Request $request, $employmentRelationshipId, $tentativaNum)
    {
        \Log::info('Atualizando Collection Attempt', [
            'employment_relationship_id' => $employmentRelationshipId,
            'tentativa_num' => $tentativaNum,
            'data_recebida' => $request->all()
        ]);
        
        $attempt = CollectionAttempt::firstOrNew([
            'employment_relationship_id' => $employmentRelationshipId,
            'tentativa_num' => $tentativaNum,
        ]);
        
        $data = $request->only([
            'endereco', 'rastreamento', 'data_envio', 'retorno', 'email', 'telefone'
        ]);
        
        \Log::info('Dados filtrados para Collection Attempt', [
            'dados_filtrados' => $data,
            'attempt_antes' => $attempt->toArray()
        ]);
        
        $attempt->fill($data);
        $attempt->save();
        
        \Log::info('Collection Attempt salvo', [
            'attempt_depois' => $attempt->toArray()
        ]);
        
        return response()->json(['success' => true, 'data' => $attempt]);
    }
} 