<?php

namespace Database\Seeders;

use App\Models\SharedInfo;
use App\Models\SharedInfoCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class SharedInfoSeeder extends Seeder
{
    public function run(): void
    {
        $joao  = User::where('email', 'joao@demo.com')->firstOrFail();
        $paulo = User::where('email', 'paulo@demo.com')->firstOrFail();
        $jorge = User::where('email', 'jorge@demo.com')->firstOrFail();
        $bingo = User::where('email', 'bingo@demo.com')->firstOrFail();

        $categories = SharedInfoCategory::whereNull('user_id')->get()->keyBy('name');

        $items = [
            [
                'user'  => $paulo,
                'title' => 'Rider Técnico — Os Bisouros (Versão Completa)',
                'description' => "Rider técnico oficial da banda para shows e festivais. Atualizado em abril/2026.\n\nEQUIPAMENTOS NECESSÁRIOS:\n• PA line array (mínimo 10kw)\n• 4 monitores de palco\n• Bateria completa (bumbo 22, caixas 12/14/16, pratos Zildjian)\n• Amplificador de guitarra Vox AC30 (para João)\n• Amplificador de guitarra Marshall (para Jorge — ele não sabe que vamos pedir o Vox)\n• Amplificador de baixo Ampeg SVT-4 (para Paulo)\n• 3 microfones Shure SM58\n• Mesa de 32 canais\n\nOBSERVAÇÕES:\n• Bingo não usa metronômico no palco. Nunca pergunte por quê.\n• O João precisa de chá de gengibre no camarim. Sem negociação.\n• Jorge vai trazer o sitar. Não tem conexão no rider. Vida.",
                'categories' => ['Produção', 'Documentação'],
                'links' => [
                    ['title' => 'Rider em PDF (versão completa)', 'url' => 'https://drive.google.com/bisouros-rider', 'description' => 'Versão completa para envio às produções'],
                    ['title' => 'Rider resumido (1 página)', 'url' => 'https://drive.google.com/bisouros-rider-resumo', 'description' => 'Versão compacta para primeiros contatos'],
                ],
            ],
            [
                'user'  => $paulo,
                'title' => 'Contatos da Gravadora EMI-Bem',
                'description' => "Informações de contato direto da gravadora para contratos, royalties e emergências.\n\nDIRETOR ARTÍSTICO:\nMr. George Maquinho\nEmail: george.m@emi-bem.com.br\nTel: (11) 3031-8800 ramal 42\n\nJURÍDICO:\nDra. Yoko Onodera\nEmail: juridico@emi-bem.com.br\nTel: (11) 3031-8800 ramal 99\n\nFINANCEIRO (royalties):\nEmail: royalties@emi-bem.com.br\n\nSUPORTE A ARTISTAS:\nHorário: Seg-Sex 10h-17h\nNão ligar às sextas após 15h. Eles desaparecem.",
                'categories' => ['Documentação', 'Produção'],
                'links' => [
                    ['title' => 'Portal do artista — EMI-Bem', 'url' => 'https://portal.emi-bem.com.br', 'description' => 'Acesso ao portal para consulta de royalties'],
                    ['title' => 'Contrato vigente (confidencial)', 'url' => 'https://drive.google.com/contrato-emi-bem', 'description' => 'Contrato assinado em 2025'],
                ],
            ],
            [
                'user'  => $joao,
                'title' => 'Checklist de Show — Do Ensaio ao Palco',
                'description' => "Lista completa de verificações antes, durante e após cada show.\n\nANTES DO SHOW (D-7):\n• Confirmar rider com a produção\n• Verificar status dos equipamentos\n• Ensaio geral\n• Confirmar transporte dos equipamentos\n\nANTES DO SHOW (D-1):\n• Passagem de som\n• Testar todos os microfones\n• Verificar afinação dos instrumentos\n• Confirmar setlist com todos\n\nNO DIA:\n• Chegada da equipe técnica (4h antes)\n• Montagem (3h antes)\n• Passagem de som (2h antes)\n• Camarim liberado (1h antes)\n• Aquecimento vocal do João (chá! sempre o chá!)\n\nPÓS-SHOW:\n• Desmontagem segura\n• Verificar se esqueceram algum equipamento\n• Verificar se o Jorge trouxe o sitar de volta",
                'categories' => ['Produção'],
                'links' => [
                    ['title' => 'Planilha de logística de shows', 'url' => 'https://docs.google.com/bisouros-logistica', 'description' => 'Planilha para controle completo'],
                ],
            ],
            [
                'user'  => $paulo,
                'title' => 'Cláusulas Padrão do Contrato de Shows',
                'description' => "Termos mínimos que a banda exige em contratos de apresentação.\n\nCACHÊ:\n• Pagamento mínimo adiantado: 50% na assinatura\n• Saldo: até 24h antes do show\n• Aceitar cheque pré-datado apenas mediante consulta ao advogado Donavan Leite\n\nHOSPITALIDADE:\n• Camarim exclusivo (não compartilhado)\n• 4 garrafas de água mineral sem gás por integrante\n• Chá de gengibre para o vocalista (obrigatório, não opcional)\n• Refeição quente disponível 2h antes do show\n\nPRODUÇÃO:\n• Sonoplasta profissional da casa\n• Passagem de som garantida (mínimo 1h)\n• Backline conforme rider\n\nDIREITOS:\n• Gravação apenas com autorização prévia por escrito\n• A banda reserva direito de veto sobre captação de áudio/vídeo",
                'categories' => ['Documentação', 'Editais'],
                'links' => [
                    ['title' => 'Modelo de contrato (Word)', 'url' => 'https://drive.google.com/bisouros-contrato-modelo', 'description' => 'Template editável'],
                    ['title' => 'Advogado: Donavan Leite', 'url' => 'https://dovanleite.adv.br', 'description' => 'Site do escritório para consultas jurídicas'],
                ],
            ],
            [
                'user'  => $joao,
                'title' => 'Kit de Imprensa — Os Bisouros (Release Oficial)',
                'description' => "RELEASE OFICIAL — OS BISOUROS\n\nOs Bisouros é uma das bandas mais importantes e improváveis da cena musical brasileira. Formada por João Leno (voz/guitarra), Paulo Macarti (baixo/piano), Jorge Cleberson (guitarra) e Bingo Estrella (bateria), a banda redefiniu o conceito de harmonia ao provar que discordância criativa pode resultar em arte genuína.\n\nDESTAQUES:\n• 10 anos de carreira e ainda na mesma banda (milagre documentado)\n• Show no Telhado: performance histórica que virou processo judicial e obra de arte simultaneamente\n• Álbum 'Pistola': em produção, aguardado como 'a obra mais ambiciosa da história deles' (Paulo Macarti, sobre si mesmo)\n\nCONTATO DE IMPRENSA:\nLinda Maccollney — assessora oficial\nlindam@bisouros.com.br | (11) 99887-6543",
                'categories' => ['Documentação', 'Produção'],
                'links' => [
                    ['title' => 'Fotos de alta resolução', 'url' => 'https://drive.google.com/bisouros-fotos-hd', 'description' => 'Sessão fotográfica 2026 — Jimmie Pageant'],
                    ['title' => 'Discografia completa', 'url' => 'https://open.spotify.com/artist/bisouros', 'description' => 'Perfil no Spotify'],
                    ['title' => 'Canal oficial YouTube', 'url' => 'https://youtube.com/@bisouros', 'description' => 'Vídeos oficiais e performances'],
                ],
            ],
            [
                'user'  => $paulo,
                'title' => 'Documentos para Inscrição em Editais Culturais',
                'description' => "Documentos recorrentes para inscrição em editais, festivais e programas de fomento cultural.\n\nDOCUMENTOS DA BANDA (PJ):\n• CNPJ (Bisouros Produções Ltda)\n• Contrato Social atualizado\n• Certidão negativa de débitos (atualizar antes de cada inscrição)\n• Portfólio artístico em PDF\n• Clipes e gravações de referência (link do drive)\n\nDOCUMENTOS DOS INTEGRANTES (PF):\n• CPF + RG de cada integrante\n• Comprovante de residência\n• Carteira de trabalho (nem todos têm — especialmente o Bingo)\n\nOBSERVAÇÕES:\n• Jorge sempre perde o documento dele. Manter cópia extra.\n• Prazo de inscrições: verificar site do ProAC, Cultura Viva e BNDES Cultural",
                'categories' => ['Editais', 'Documentação'],
                'links' => [
                    ['title' => 'ProAC — Programa de Ação Cultural SP', 'url' => 'https://proac.sp.gov.br', 'description' => 'Principal edital do estado de São Paulo'],
                    ['title' => 'Drive com documentos da banda', 'url' => 'https://drive.google.com/bisouros-documentos', 'description' => 'Pasta com todos os documentos atualizados'],
                    ['title' => 'BNDES Cultural', 'url' => 'https://bndes.gov.br/cultural', 'description' => 'Editais federais de cultura'],
                ],
            ],
            [
                'user'  => $bingo,
                'title' => 'Histórico de Shows e Apresentações',
                'description' => "HISTÓRICO DE SHOWS — OS BISOUROS\n\n2026:\n• Show no Telhado (fevereiro) — Edifício da Gravadora, SP — HISTÓRICO E PROCESSADO\n• Blue Note SP (abril previsto) — SP\n• Festival Estoque de Madeira (maio) — SP\n• Vibra São Paulo (maio) — SP\n\n2025:\n• Circo Voador — Rio de Janeiro (3 apresentações)\n• Vivo Rio — Rio de Janeiro\n• Bourbon Street — SP (série de shows de outubro)\n• Auditório Araújo Vianna — Porto Alegre\n\n2024:\n• Blue Note SP — SP (estreia no Blue Note)\n• Espaço Unimed — SP\n• Festival de Inverno Campos do Jordão\n\n2023:\n• Estúdio ao Vivo — gravação ao vivo para álbum\n• Tour brasileira: 12 cidades\n\nTOTAL ACUMULADO: 87 shows + 1 telhado",
                'categories' => ['Produção'],
                'links' => [
                    ['title' => 'Planilha completa de shows', 'url' => 'https://docs.google.com/bisouros-historico-shows', 'description' => 'Histórico detalhado com cachet, público, avaliação'],
                ],
            ],
        ];

        foreach ($items as $item) {
            $info = SharedInfo::updateOrCreate(
                ['user_id' => $item['user']->id, 'title' => $item['title']],
                ['description' => $item['description']]
            );

            $info->categories()->sync(
                collect($item['categories'])
                    ->map(fn (string $name) => $categories->get($name)?->id)
                    ->filter()
                    ->values()
                    ->all()
            );

            $info->links()->delete();
            foreach ($item['links'] as $link) {
                $info->links()->create($link);
            }
        }
    }
}