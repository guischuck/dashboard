<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\LegalCase;
use App\Models\EmploymentRelationship;

// Simula dados de vínculos empregatícios extraídos do CNIS
$vinculos_empregaticios = [
    [
        'empregador' => 'EDI AGUIAR LEONE',
        'cnpj' => '00.239.365/0001-73',
        'data_inicio' => '01/08/1996',
        'data_fim' => '31/12/1996',
        'salario' => '112,00'
    ],
    [
        'empregador' => 'CABRAL REFRIGERACAO LTDA',
        'cnpj' => '78.839.529/0002-71',
        'data_inicio' => '01/12/1998',
        'data_fim' => '03/02/2000',
        'salario' => '10,00'
    ]
];

echo "=== TESTE DE ENVIO DE VÍNCULOS ===\n";
echo "Dados de vínculos a serem enviados:\n";
print_r($vinculos_empregaticios);

// Simula o processamento do controller
echo "\n=== SIMULANDO PROCESSAMENTO DO CONTROLLER ===\n";

$validated = [
    'client_name' => 'TESTE CLIENTE',
    'client_cpf' => '123.456.789-00',
    'vinculos_empregaticios' => $vinculos_empregaticios
];

echo "Dados validados:\n";
print_r($validated);

// Simula a criação do caso
$case = new LegalCase();
$case->client_name = $validated['client_name'];
$case->client_cpf = $validated['client_cpf'];
$case->case_number = 'TESTE-2025-001';
$case->created_by = 1;
$case->status = 'pending';

echo "\nCaso a ser criado:\n";
echo "ID: " . ($case->id ?? 'NOVO') . "\n";
echo "Nome: " . $case->client_name . "\n";
echo "CPF: " . $case->client_cpf . "\n";
echo "Número: " . $case->case_number . "\n";

// Simula o salvamento dos vínculos
if (!empty($validated['vinculos_empregaticios'])) {
    echo "\n=== SALVANDO VÍNCULOS ===\n";
    
    foreach ($validated['vinculos_empregaticios'] as $vinculo) {
        echo "Salvando vínculo:\n";
        echo "- Empregador: " . $vinculo['empregador'] . "\n";
        echo "- CNPJ: " . $vinculo['cnpj'] . "\n";
        echo "- Início: " . $vinculo['data_inicio'] . "\n";
        echo "- Fim: " . $vinculo['data_fim'] . "\n";
        echo "- Salário: " . $vinculo['salario'] . "\n";
        
        // Simula a criação do vínculo
        $employmentRelationship = new EmploymentRelationship();
        $employmentRelationship->employer_name = $vinculo['empregador'];
        $employmentRelationship->employer_cnpj = $vinculo['cnpj'];
        $employmentRelationship->start_date = $vinculo['data_inicio'];
        $employmentRelationship->end_date = $vinculo['data_fim'];
        $employmentRelationship->salary = (float) str_replace(',', '.', $vinculo['salario']);
        $employmentRelationship->is_active = empty($vinculo['data_fim']);
        $employmentRelationship->notes = 'Extraído automaticamente do CNIS';
        
        echo "- Vínculo criado com sucesso!\n\n";
    }
    
    echo "Total de vínculos processados: " . count($validated['vinculos_empregaticios']) . "\n";
} else {
    echo "\nERRO: Nenhum vínculo empregatício fornecido!\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n"; 