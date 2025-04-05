<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProntuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all patient IDs
        $pacienteIds = DB::table('pacientes')->pluck('id')->toArray();

        // Clinical conditions
        $condicoesClinicos = [
            'Lombalgia crônica', 'Cervicalgia', 'Tendinite', 'Bursite', 'Artrose', 'Artrite',
            'Hérnia de disco', 'Escoliose', 'Cifose', 'Lordose', 'Espondilite anquilosante',
            'Fibromialgia', 'Osteoporose', 'Condromalácia patelar', 'Síndrome do túnel do carpo',
            'Epicondilite lateral', 'Epicondilite medial', 'Síndrome do impacto', 'Fascite plantar',
            'Distensão muscular', 'Entorse de tornozelo', 'Lesão do ligamento cruzado anterior',
            'Lesão do menisco', 'Síndrome do piriforme', 'Dor miofascial', 'Cefaleia tensional',
            'Paralisia facial', 'AVC', 'Doença de Parkinson', 'Esclerose múltipla'
        ];

        // Main complaints
        $queixasPrincipais = [
            'Dor lombar', 'Dor cervical', 'Dor no ombro', 'Dor no joelho', 'Dor no cotovelo',
            'Dor no punho', 'Dor no tornozelo', 'Dificuldade para andar', 'Dificuldade para levantar',
            'Dificuldade para sentar', 'Dificuldade para dormir', 'Formigamento', 'Dormência',
            'Fraqueza muscular', 'Rigidez articular', 'Limitação de movimento', 'Instabilidade articular',
            'Edema', 'Crepitação', 'Dor ao movimento', 'Dor em repouso', 'Dor noturna',
            'Dificuldade para realizar atividades diárias', 'Dificuldade para praticar esportes'
        ];

        // Life habits
        $habitosVida = [
            'Sedentário', 'Pratica atividade física regularmente', 'Pratica atividade física ocasionalmente',
            'Tabagista', 'Ex-tabagista', 'Etilista', 'Ex-etilista', 'Alimentação balanceada',
            'Alimentação desregrada', 'Sono regular', 'Insônia', 'Estresse elevado',
            'Trabalho que exige esforço físico', 'Trabalho sedentário', 'Trabalho repetitivo'
        ];

        // Medications
        $medicamentos = [
            'Anti-inflamatórios', 'Analgésicos', 'Relaxantes musculares', 'Corticoides',
            'Anti-hipertensivos', 'Hipoglicemiantes', 'Antidepressivos', 'Ansiolíticos',
            'Anticonvulsivantes', 'Hormônios', 'Suplementos', 'Vitaminas', 'Fitoterápicos'
        ];

        // Therapeutic resources
        $recursosTerapeuticos = [
            'Cinesioterapia', 'Eletroterapia', 'Termoterapia', 'Crioterapia', 'Hidroterapia',
            'Terapia manual', 'Massoterapia', 'Pilates', 'RPG', 'Acupuntura', 'Ventosaterapia',
            'Bandagem funcional', 'Liberação miofascial', 'Mobilização articular', 'Exercícios funcionais',
            'Fortalecimento muscular', 'Alongamento', 'Propriocepção', 'Treino de marcha',
            'Treino de equilíbrio', 'Treino respiratório', 'Drenagem linfática'
        ];

        // Treatment plans
        $planosTratamento = [
            'Atendimento 2x por semana por 1 mês', 'Atendimento 3x por semana por 1 mês',
            'Atendimento 2x por semana por 2 meses', 'Atendimento 1x por semana por 3 meses',
            'Atendimento 2x por semana por 3 meses', 'Atendimento 3x por semana por 2 meses',
            'Atendimento 1x por semana por 2 meses', 'Atendimento 2x por semana por 1 mês, seguido de reavaliação'
        ];

        // Generate 80 random medical records (for 80% of patients)
        $prontuarios = [];
        $pacientesComProntuario = array_slice($pacienteIds, 0, (int)(count($pacienteIds) * 0.8));

        foreach ($pacientesComProntuario as $pacienteId) {
            // Generate random data for each field
            $historiaClinical = $condicoesClinicos[array_rand($condicoesClinicos)] . '. ' .
                              ($this->randomChance(70) ? $condicoesClinicos[array_rand($condicoesClinicos)] . '. ' : '') .
                              'Paciente relata ' . strtolower($queixasPrincipais[array_rand($queixasPrincipais)]) . ' há ' .
                              rand(1, 24) . ' ' . ($this->randomChance(50) ? 'meses' : 'semanas') . '.';

            $queixaPrincipal = $queixasPrincipais[array_rand($queixasPrincipais)] .
                              ($this->randomChance(40) ? ' e ' . strtolower($queixasPrincipais[array_rand($queixasPrincipais)]) : '') . '.';

            $habitosVidaText = $habitosVida[array_rand($habitosVida)] . '. ' .
                              ($this->randomChance(80) ? $habitosVida[array_rand($habitosVida)] . '. ' : '') .
                              ($this->randomChance(50) ? $habitosVida[array_rand($habitosVida)] . '.' : '');

            $hma = 'Paciente relata que ' . ($this->randomChance(50) ? 'a dor iniciou ' : 'os sintomas iniciaram ') .
                  'após ' . ($this->randomChance(50) ? 'esforço físico intenso' : 'movimento brusco') . '. ' .
                  ($this->randomChance(70) ? 'Refere piora ' . ($this->randomChance(50) ? 'ao final do dia' : 'pela manhã') . '. ' : '') .
                  ($this->randomChance(60) ? 'Já realizou tratamento ' . ($this->randomChance(50) ? 'medicamentoso' : 'fisioterapêutico') . ' anteriormente com ' .
                  ($this->randomChance(50) ? 'melhora parcial' : 'pouca melhora') . '.' : '');

            $hmp = $this->randomChance(30) ? 'Sem histórico de patologias prévias. ' :
                  'Histórico de ' . $condicoesClinicos[array_rand($condicoesClinicos)] . ' há ' . rand(1, 10) . ' anos. ' .
                  ($this->randomChance(50) ? 'Realizou tratamento com ' . ($this->randomChance(50) ? 'boa' : 'parcial') . ' resposta.' : '');

            $antecedentesPessoais = $this->randomChance(40) ? 'Nega comorbidades. ' :
                                   'Paciente ' . ($this->randomChance(50) ? 'hipertenso' : 'diabético') . '. ' .
                                   ($this->randomChance(30) ? 'Histórico de ' . ($this->randomChance(50) ? 'fratura' : 'cirurgia') . ' em ' .
                                   ($this->randomChance(50) ? 'membro inferior' : 'membro superior') . '.' : '');

            $antecedentesFamiliares = $this->randomChance(60) ? 'Sem antecedentes familiares relevantes. ' :
                                     'Histórico familiar de ' . ($this->randomChance(50) ? 'hipertensão' : 'diabetes') . '. ' .
                                     ($this->randomChance(40) ? 'Pai com histórico de ' . $condicoesClinicos[array_rand($condicoesClinicos)] . '.' : '');

            $tratamentosRealizados = $this->randomChance(50) ? 'Sem tratamentos prévios. ' :
                                    'Já realizou ' . ($this->randomChance(50) ? 'fisioterapia' : 'tratamento medicamentoso') . ' por ' .
                                    rand(1, 6) . ' meses com ' . ($this->randomChance(50) ? 'boa' : 'parcial') . ' resposta.';

            $usaMedicamentos = $this->randomChance(60) ? 'Faz uso de ' . $medicamentos[array_rand($medicamentos)] .
                              ($this->randomChance(50) ? ' e ' . $medicamentos[array_rand($medicamentos)] : '') . '.' :
                              'Não faz uso de medicamentos contínuos.';

            $realizouCirurgia = $this->randomChance(30) ? 'Realizou cirurgia de ' .
                               ($this->randomChance(50) ? 'joelho' : 'coluna') . ' há ' . rand(1, 10) . ' anos.' :
                               'Nega procedimentos cirúrgicos prévios.';

            $inspecaoPalpacao = 'À inspeção, ' . ($this->randomChance(50) ? 'sem alterações visíveis' : 'observa-se ' .
                               ($this->randomChance(50) ? 'edema' : 'hiperemia') . ' em região ' .
                               ($this->randomChance(50) ? 'lombar' : 'cervical')) . '. ' .
                               'À palpação, ' . ($this->randomChance(50) ? 'presença de pontos gatilho em ' : 'dor à palpação em ') .
                               ($this->randomChance(50) ? 'musculatura paravertebral' : 'região do trapézio') . '.';

            $semiotica = 'Paciente apresenta ' . ($this->randomChance(50) ? 'limitação de amplitude de movimento' : 'dor à movimentação ativa') .
                        ' em ' . ($this->randomChance(50) ? 'flexão' : 'rotação') . '. ' .
                        ($this->randomChance(70) ? 'Teste de ' . ($this->randomChance(50) ? 'força muscular' : 'sensibilidade') . ' ' .
                        ($this->randomChance(50) ? 'preservado' : 'alterado') . '.' : '');

            $testesEspecificos = 'Teste de ' . ($this->randomChance(50) ? 'Lasègue' : 'Phalen') . ' ' .
                               ($this->randomChance(50) ? 'positivo' : 'negativo') . '. ' .
                               ($this->randomChance(70) ? 'Teste de ' . ($this->randomChance(50) ? 'Finkelstein' : 'McMurray') . ' ' .
                               ($this->randomChance(50) ? 'positivo' : 'negativo') . '.' : '');

            $avaliacaoDor = 'Dor ' . ($this->randomChance(50) ? 'contínua' : 'intermitente') . ', ' .
                           ($this->randomChance(50) ? 'de forte intensidade (EVA ' . rand(7, 10) . '/10)' : 'de moderada intensidade (EVA ' . rand(4, 6) . '/10)') . ', ' .
                           ($this->randomChance(50) ? 'que piora com movimento' : 'que piora em repouso') . '.';

            $objetivosTratamento = 'Redução do quadro álgico. ' .
                                 ($this->randomChance(80) ? 'Aumento da amplitude de movimento. ' : '') .
                                 ($this->randomChance(70) ? 'Fortalecimento muscular. ' : '') .
                                 ($this->randomChance(60) ? 'Melhora da funcionalidade. ' : '') .
                                 ($this->randomChance(50) ? 'Orientações posturais e ergonômicas.' : '');

            $recursosTerapeuticosText = $recursosTerapeuticos[array_rand($recursosTerapeuticos)] . ', ' .
                                      $recursosTerapeuticos[array_rand($recursosTerapeuticos)] . ', ' .
                                      $recursosTerapeuticos[array_rand($recursosTerapeuticos)] .
                                      ($this->randomChance(50) ? ', ' . $recursosTerapeuticos[array_rand($recursosTerapeuticos)] : '') . '.';

            $planoTratamento = $planosTratamento[array_rand($planosTratamento)] . '. ' .
                              'Reavaliação após ' . rand(5, 15) . ' sessões.';

            $diagnosticoClinico = $condicoesClinicos[array_rand($condicoesClinicos)] .
                                ($this->randomChance(30) ? ' associado a ' . strtolower($condicoesClinicos[array_rand($condicoesClinicos)]) : '') . '.';

            $diagnosticoFisioterapeutico = 'Paciente apresenta ' .
                                         ($this->randomChance(50) ? 'limitação funcional' : 'quadro álgico') . ' em ' .
                                         ($this->randomChance(50) ? 'região lombar' : 'região cervical') . ' ' .
                                         ($this->randomChance(50) ? 'com irradiação para membro' : 'sem irradiação') . ', ' .
                                         ($this->randomChance(50) ? 'associado a espasmo muscular' : 'associado a fraqueza muscular') . '.';

            // Create timestamp for data_criacao
            $dataCriacao = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d H:i:s');

            // Create medical record
            $prontuarios[] = [
                'id_paciente' => $pacienteId,
                'historia_clinica' => $historiaClinical,
                'queixa_principal' => $queixaPrincipal,
                'habitos_vida' => $habitosVidaText,
                'hma' => $hma,
                'hmp' => $hmp,
                'antecedentes_pessoais' => $antecedentesPessoais,
                'antecedentes_familiares' => $antecedentesFamiliares,
                'tratamentos_realizados' => $tratamentosRealizados,
                'deambulando' => $this->randomChance(90) ? 1 : 0,
                'internado' => $this->randomChance(10) ? 1 : 0,
                'deambulando_apoio' => $this->randomChance(20) ? 1 : 0,
                'orientado' => $this->randomChance(95) ? 1 : 0,
                'cadeira_rodas' => $this->randomChance(5) ? 1 : 0,
                'exames_complementares' => $this->randomChance(60) ? 'Realizou ' . ($this->randomChance(50) ? 'radiografia' : 'ressonância magnética') . ' que evidenciou ' . strtolower($condicoesClinicos[array_rand($condicoesClinicos)]) . '.' : 'Sem exames complementares.',
                'usa_medicamentos' => $usaMedicamentos,
                'realizou_cirurgia' => $realizouCirurgia,
                'inspecao_palpacao' => $inspecaoPalpacao,
                'semiotica' => $semiotica,
                'testes_especificos' => $testesEspecificos,
                'avaliacao_dor' => $avaliacaoDor,
                'objetivos_tratamento' => $objetivosTratamento,
                'recursos_terapeuticos' => $recursosTerapeuticosText,
                'plano_tratamento' => $planoTratamento,
                'diagnostico_clinico' => $diagnosticoClinico,
                'diagnostico_fisioterapeutico' => $diagnosticoFisioterapeutico,
                'data_criacao' => $dataCriacao
            ];
        }

        // Insert data into the prontuarios table
        DB::table('prontuarios')->insert($prontuarios);
    }

    /**
     * Helper function to generate random chance
     */
    private function randomChance($percentageChance)
    {
        return rand(1, 100) <= $percentageChance;
    }
}
