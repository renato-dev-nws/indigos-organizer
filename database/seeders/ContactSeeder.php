<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $joao  = User::where('email', 'joao@demo.com')->firstOrFail();
        $paulo = User::where('email', 'paulo@demo.com')->firstOrFail();
        $jorge = User::where('email', 'jorge@demo.com')->firstOrFail();
        $bingo = User::where('email', 'bingo@demo.com')->firstOrFail();

        $venues = Venue::orderBy('name')->get()->keyBy('name');

        $contacts = [
            // ── Produção e Gestão ──────────────────────────────────────
            [
                'user'        => $paulo,
                'venue'       => null,
                'name'        => 'Sérgio Menendes',
                'email'       => 'sergio@producoes-menendes.com.br',
                'phone'       => '(11) 97651-3300',
                'whatsapp'    => '5511976513300',
                'description' => 'Produtor musical sênior. Trabalhou com os maiores nomes da MPB, jazz e bossa nova. Fã declarado dos Beatles (o original). Adora os Bisouros mas finge que não. Disponível para pré-produção do Álbum Pistola.',
            ],
            [
                'user'        => $paulo,
                'venue'       => null,
                'name'        => 'Brian Épistola',
                'email'       => 'brian@epola-management.com.br',
                'phone'       => '(11) 98877-1234',
                'whatsapp'    => '5511988771234',
                'description' => 'Empresário da banda. Negociador feroz. Assinou os maiores contratos dos Bisouros, inclusive o da EMI-Bem. Não suporta ser chamado de "agente". É empresário. Repita: empresário.',
            ],
            [
                'user'        => $joao,
                'venue'       => null,
                'name'        => 'Linda Maccollney',
                'email'       => 'lindam@bisouros.com.br',
                'phone'       => '(11) 99887-6543',
                'whatsapp'    => '5511998876543',
                'description' => 'Assessora de imprensa oficial dos Bisouros desde 2023. Gerencia a relação com jornalistas, blogs musicais e a imprensa chata. Também cuida do Instagram quando o João resolve postar coisas "polêmicas" às 2h da manhã.',
            ],
            [
                'user'        => $paulo,
                'venue'       => null,
                'name'        => 'Donavan Leite',
                'email'       => 'donavan@dovanleite.adv.br',
                'phone'       => '(11) 3311-9090',
                'whatsapp'    => '5511991009090',
                'description' => 'Advogado da banda. Especializado em direito autoral, contratos de entretenimento e defesa de artistas que fazem shows no telhado sem autorização. Trabalha muito com os Bisouros. Cobra caro. Merece.',
            ],
            [
                'user'        => $paulo,
                'venue'       => null,
                'name'        => 'Yoko Onodera',
                'email'       => 'yoko@emi-bem.com.br',
                'phone'       => '(11) 3031-8800',
                'whatsapp'    => '5511993031880',
                'description' => 'Representante jurídica da gravadora EMI-Bem. Especializada em direitos autorais internacionais e licenciamento. É ela quem assina os contratos com a banda. Conhecida por ler cada vírgula do contrato em voz alta. A reunião dura 4 horas.',
            ],

            // ── Músicos Convidados e Colaboradores ─────────────────────
            [
                'user'        => $joao,
                'venue'       => null,
                'name'        => 'Éric Clápeton',
                'email'       => 'eric@clapeton.co.uk',
                'phone'       => '+44 20 7946 0958',
                'whatsapp'    => '447912345678',
                'description' => 'Guitarrista britânico lendário. Participou de uma faixa secreta do álbum Pistola. Jorge ainda não sabe. Terceira turnê de despedida em andamento. Cobra em libras, toca em qualquer key.',
            ],
            [
                'user'        => $paulo,
                'venue'       => null,
                'name'        => 'George Hairson',
                'email'       => 'george.h@guitarrasessao.com.br',
                'phone'       => '(11) 94455-7788',
                'whatsapp'    => '5511944557788',
                'description' => 'Guitarrista de sessão que toca tudo que Jorge não consegue executar direito. Discreto, pontual e nunca opina sobre a arte. As qualidades que Jorge devia ter. Toca slide, fingerpicking, riff 60s e qualquer coisa em 1 take.',
            ],
            [
                'user'        => $bingo,
                'venue'       => null,
                'name'        => 'Ringo Starkey',
                'email'       => 'ringo@percussaobrasil.com.br',
                'phone'       => '(21) 98866-3322',
                'whatsapp'    => '5521988663322',
                'description' => 'Baterista de sessão e percussionista. Bindo o chama quando precisa de uma segunda opinião sobre tempo e groove. Toca na mesma frequência que Bingo — o que é assustador porque eles nunca ensaiaram juntos.',
            ],
            [
                'user'        => $paulo,
                'venue'       => null,
                'name'        => 'Suzi Quartzo',
                'email'       => 'suzi@suziquartzo.com.br',
                'phone'       => '(11) 97712-4411',
                'whatsapp'    => '5511977124411',
                'description' => 'Artista que abre shows dos Bisouros desde 2024. Autora do EP "Quilométrica". Voz potente, presença de palco, faz o público aquecido antes da banda entrar. A banda torce por ela. Bingo especialmente.',
            ],
            [
                'user'        => $joao,
                'venue'       => null,
                'name'        => 'Tom Waiters Jr.',
                'email'       => 'tom.jr@roteiroscavernosos.com.br',
                'phone'       => '(11) 96633-0099',
                'whatsapp'    => '5511966330099',
                'description' => 'Roteirista e diretor criativo. Foi contratado para escrever o roteiro do documentário do show no telhado. Entregou 47 páginas sobre "o existencialismo da arte ao ar livre urbano". Paulo leu tudo. João leu o título.',
            ],

            // ── Técnicos e Produção Visual ──────────────────────────────
            [
                'user'        => $joao,
                'venue'       => 'Blue Note SP',
                'name'        => 'Jimmie Pageant',
                'email'       => 'jimmie@pageantfoto.com.br',
                'phone'       => '(11) 97744-0011',
                'whatsapp'    => '5511977440011',
                'description' => 'Fotógrafo oficial dos Bisouros. Também faz iluminação de shows. Responsável pelas fotos do kit de imprensa 2026 e pela sessão fotográfica clássica dos quatro integrantes cruzando a rua Consolação. João disse que foi ideia dele. Paulo discorda.',
            ],
            [
                'user'        => $paulo,
                'venue'       => null,
                'name'        => 'Keith Richartes',
                'email'       => 'keith.richartes@sonoplastia.com.br',
                'phone'       => '(11) 99334-8800',
                'whatsapp'    => '5511993348800',
                'description' => 'Técnico de som de palco. Faz a sonoplastia ao vivo nos maiores shows da banda. Tem ouvido absoluto e temperamento controlado (ao contrário do João quando alguma coisa está desafinada). Parceiro de décadas.',
            ],
            [
                'user'        => $joao,
                'venue'       => null,
                'name'        => 'Björk Oliveira',
                'email'       => 'bjork@clipadora.com.br',
                'phone'       => '(11) 95544-7766',
                'whatsapp'    => '5511955447766',
                'description' => 'Diretora de videoclipes. Dirigiu 3 dos últimos clipes dos Bisouros. Gosta de conceitos visuais ousados. Quando propôs o clipe do telhado, todos acharam que era metáfora. Não era.',
            ],
            [
                'user'        => $paulo,
                'venue'       => null,
                'name'        => 'David Bowiezo',
                'email'       => 'david.b@estilozero.com.br',
                'phone'       => '(11) 94422-1199',
                'whatsapp'    => '5511944221199',
                'description' => 'Stylist e consultor de imagem da banda. Responsável pelos looks de palco dos Bisouros. Propôs a identidade visual "beatnik intergalático" do álbum Pistola. Jorge queria algo "mais étnico". David educadamente ignorou.',
            ],

            // ── Rival Amigável ─────────────────────────────────────────
            [
                'user'        => $jorge,
                'venue'       => null,
                'name'        => 'Mick Jaguaribe',
                'email'       => 'mick@jaguaribeband.com.br',
                'phone'       => '(21) 99001-5544',
                'whatsapp'    => '5521990015544',
                'description' => 'Vocalista da banda rival Os Jaguaribos. Rivalidade histórica, mas saudável. Sempre comparado com João Leno (para irritação mútua). Jorge é o único dos Bisouros que tem contato com Mick. Os outros fingem que ele não existe.',
            ],
        ];

        foreach ($contacts as $contactData) {
            $venueModel = $contactData['venue'] ? $venues->get($contactData['venue']) : null;

            Contact::updateOrCreate(
                ['email' => $contactData['email']],
                [
                    'user_id'     => $contactData['user']->id,
                    'venue_id'    => $venueModel?->id,
                    'name'        => $contactData['name'],
                    'phone'       => $contactData['phone'],
                    'whatsapp'    => $contactData['whatsapp'],
                    'description' => $contactData['description'],
                ]
            );
        }
    }
}
