<?php

function store_flash_sale_table_exists(DatabaseConnection $conn): bool
{
    $statement = $conn->prepare(
        "SELECT EXISTS (
             SELECT 1
         FROM information_schema.tables
             WHERE table_schema = current_schema() AND table_name = 'flash_sale_tbl'
         ) AS table_exists"
    );
    $statement->execute();
    $tableExists = $statement->get_result()->fetch_assoc()['table_exists'];
    $exists = in_array($tableExists, [true, 't', 1, '1'], true);
    $statement->close();

    return $exists;
}

function store_flash_sale_rows(DatabaseConnection $conn, string $state, DateTimeImmutable $now): array
{
    $nowValue = $now->format('Y-m-d H:i:s');
    $availableStock = store_available_stock_sql();

    $sql = "SELECT p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date,
                   c.category_id, c.category_name, v.variant_id, v.variant_name, v.price, v.compare_at_price,
                   v.currency, {$availableStock} AS stock_quantity, i.image_url, i.alt_text,
                   (
                       SELECT t.talent_name
                       FROM product_talents_tbl pt_name
                       INNER JOIN talent_tbl t ON t.talent_id = pt_name.talent_id
                       WHERE pt_name.product_id = p.product_id
                       ORDER BY t.talent_id ASC
                       LIMIT 1
                   ) AS talent_name,
                   (
                       SELECT t.talent_slug
                       FROM product_talents_tbl pt_slug
                       INNER JOIN talent_tbl t ON t.talent_id = pt_slug.talent_id
                       WHERE pt_slug.product_id = p.product_id
                       ORDER BY t.talent_id ASC
                       LIMIT 1
                   ) AS talent_slug,
                   (
                       SELECT t.talent_img
                       FROM product_talents_tbl pt_img
                       INNER JOIN talent_tbl t ON t.talent_id = pt_img.talent_id
                       WHERE pt_img.product_id = p.product_id
                       ORDER BY t.talent_id ASC
                       LIMIT 1
                   ) AS talent_img,
                   (
                       SELECT tg.group_name
                       FROM product_talents_tbl pt_grp
                       INNER JOIN talent_group_members_tbl gm ON gm.talent_id = pt_grp.talent_id
                       INNER JOIN talent_group_tbl tg ON tg.group_id = gm.group_id
                       WHERE pt_grp.product_id = p.product_id
                       ORDER BY pt_grp.talent_id ASC, tg.group_id ASC
                       LIMIT 1
                   ) AS talent_group,
                   fs.flash_sale_id, fs.starts_at, fs.ends_at
            FROM product_tbl p
            INNER JOIN category_tbl c ON c.category_id = p.category_id
            INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
            LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
            LEFT JOIN flash_sale_tbl fs ON fs.variant_id = v.variant_id AND fs.status = 'scheduled'
            WHERE p.status = 'active'
              AND v.compare_at_price IS NOT NULL
              AND v.compare_at_price > v.price
              AND fs.flash_sale_id IS NOT NULL";

    if ($state === 'live') {
        $sql .= " AND {$availableStock} > 0
                  AND fs.starts_at <= ? AND fs.ends_at > ?
                  ORDER BY fs.ends_at ASC, p.product_id DESC";
        $statement = $conn->prepare($sql);
        $statement->bind_param('ss', $nowValue, $nowValue);
    } elseif ($state === 'starting') {
        $endOfWindow = $now->modify('+1 hour')->format('Y-m-d H:i:s');
        $sql .= " AND {$availableStock} > 0 AND fs.starts_at > ? AND fs.starts_at <= ?
                  ORDER BY fs.starts_at ASC, p.product_id DESC";
        $statement = $conn->prepare($sql);
        $statement->bind_param('ss', $nowValue, $endOfWindow);
    } elseif ($state === 'tomorrow') {
        $tomorrow = $now->modify('tomorrow')->setTime(0, 0)->format('Y-m-d H:i:s');
        $dayAfter = $now->modify('+2 days')->setTime(0, 0)->format('Y-m-d H:i:s');
        $sql .= " AND {$availableStock} > 0 AND fs.starts_at >= ? AND fs.starts_at < ?
                  ORDER BY fs.starts_at ASC, p.product_id DESC";
        $statement = $conn->prepare($sql);
        $statement->bind_param('ss', $tomorrow, $dayAfter);
    } elseif ($state === 'archive') {
        $sql .= ' AND fs.ends_at <= ? ORDER BY fs.ends_at DESC, p.product_id DESC';
        $statement = $conn->prepare($sql);
        $statement->bind_param('s', $nowValue);
    } else {
        throw new InvalidArgumentException('Choose a valid flash sale state.');
    }

    $statement->execute();
    $rows = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();

    return $rows;
}

function store_flash_sale_talent_meta(
    string $talentSlug,
    string $talentName,
    ?string $talentImg = null,
    ?string $groupName = null
): array {
    $cleanName = trim($talentName) !== '' ? trim($talentName) : 'hololive production';
    $normalizedSlug = trim(strtolower($talentSlug));
    if ($normalizedSlug === '') {
        $normalizedSlug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $cleanName), '-'));
    }

    $curated = [
        'gawr-gura' => [
            'japaneseName' => 'がうる・ぐら',
            'unit' => 'hololive English -Myth-',
            'generation' => 'HoloEN Myth',
            'fanName' => 'chumbuds',
            'themeColor' => '#00c3ff',
            'accentColor' => '#1e40af',
            'glowColor' => 'rgba(0, 195, 255, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2024/05/bg_Gawr-Gura_01.png',
            'prVisualAlt' => 'https://hololive.hololivepro.com/wp-content/uploads/2024/05/Gawr-Gura_pr-img_01.png',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Gawr-Gura_list_thumb.png',
            'quote' => '“Domo!! Sa-me desu!! Have you had shark thoughts today? Snatch this flash sale before it swims away! A!”',
            'rarity' => 'MYTHIC',
        ],
        'houshou-marine' => [
            'japaneseName' => '宝鐘マリン',
            'unit' => 'hololive 3rd Gen (HoloFantasy)',
            'generation' => 'HoloFantasy 3期生',
            'fanName' => 'Houshou Pirates',
            'themeColor' => '#ff2a6d',
            'accentColor' => '#fbbf24',
            'glowColor' => 'rgba(255, 42, 109, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/bg_Houshou-Marine_01.png',
            'prVisualAlt' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Houshou-Marine_pr-img_01.png',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Houshou-Marine_list_thumb.png',
            'quote' => '“Ahoy!! Houshou Pirate Captain Houshou Marine desu! All crew members, plunder this flash vault right now!”',
            'rarity' => 'MYTHIC',
        ],
        'usada-pekora' => [
            'japaneseName' => '兎田ぺこら',
            'unit' => 'hololive 3rd Gen (HoloFantasy)',
            'generation' => 'HoloFantasy 3期生',
            'fanName' => 'Nousagi',
            'themeColor' => '#38bdf8',
            'accentColor' => '#f97316',
            'glowColor' => 'rgba(56, 189, 248, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/bg_Usada-Pekora_01.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Usada-Pekora_list_thumb.png',
            'quote' => '“Hiya-peko! Hiya-pekooo! Usada Pekora desu peko! Almond almond, don’t miss this insane flash deal peko!!”',
            'rarity' => 'LEGENDARY',
        ],
        'hoshimachi-suisei' => [
            'japaneseName' => '星街すいせい',
            'unit' => 'hololive Gen 0',
            'generation' => 'Gen 0 / MGO',
            'fanName' => 'Hoshiyomi',
            'themeColor' => '#6366f1',
            'accentColor' => '#06b6d4',
            'glowColor' => 'rgba(99, 102, 241, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/bg_Hoshimachi-Suisei_01.png',
            'prVisualAlt' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Hoshimachi-Suisei_pr-img_01.png',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Hoshimachi-Suisei_list_thumb.png',
            'quote' => '“Sui-chan wa kyou mo kawaii!! Like a comet shining in the night sky, this Holoverse flash sale is here for you!”',
            'rarity' => 'MYTHIC',
        ],
        'mori-calliope' => [
            'japaneseName' => '森カリオペ',
            'unit' => 'hololive English -Myth-',
            'generation' => 'HoloEN Myth',
            'fanName' => 'Dead Beats',
            'themeColor' => '#ec4899',
            'accentColor' => '#a855f7',
            'glowColor' => 'rgba(236, 72, 153, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/bg_Mori-Calliope_01.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Mori-Calliope_list_thumb.png',
            'quote' => '“Guh! Whaddup Dead Beats! The Reaper is in the house slicing prices right off this Holoverse flash drop! Let’s go!”',
            'rarity' => 'LEGENDARY',
        ],
        'sakura-miko' => [
            'japaneseName' => 'さくらみこ',
            'unit' => 'hololive Gen 0',
            'generation' => 'Gen 0 / MiComet',
            'fanName' => '35P',
            'themeColor' => '#fb7185',
            'accentColor' => '#f59e0b',
            'glowColor' => 'rgba(251, 113, 133, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/bg_Sakura-Miko_01.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Sakura-Miko_talent_list_thumb.png',
            'quote' => '“Nyahello!! Sakura Miko desu! Elite flash sale has commenced! Grab the elite goods before Miko burns them by accident, nya!”',
            'rarity' => 'LEGENDARY',
        ],
        'inugami-korone' => [
            'japaneseName' => '戌神ころね',
            'unit' => 'hololive GAMERS',
            'generation' => 'GAMERS',
            'fanName' => 'Koronesuki',
            'themeColor' => '#f59e0b',
            'accentColor' => '#ef4444',
            'glowColor' => 'rgba(245, 158, 11, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2023/04/bg_Inugami-Korone_01.png',
            'prVisualAlt' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Inugami-Korone_pr-img_01.png',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Inugami-Korone_talent_list_thumb.png',
            'quote' => '“Oha-yo! Yubi yubi!! Korone ga kita zo! Give me your yubis and take this flash deal right into your cart!”',
            'rarity' => 'EPIC',
        ],
        'hakui-koyori' => [
            'japaneseName' => '博衣こより',
            'unit' => 'hololive 6th Gen (Secret Society holoX)',
            'generation' => 'holoX 6期生',
            'fanName' => 'Koyote / Joshu',
            'themeColor' => '#f472b6',
            'accentColor' => '#ec4899',
            'glowColor' => 'rgba(244, 114, 182, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Hakui-Koyori_list_thumb.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Hakui-Koyori_list_thumb.png',
            'quote' => '“Konkoyo~! Brain of holoX, Hakui Koyori desu! My lab just synthesized this limited flash drop—grab yours before the experiment ends!”',
            'rarity' => 'LEGENDARY',
        ],
        'shirakami-fubuki' => [
            'japaneseName' => '白上フブキ',
            'unit' => 'hololive 1st Gen / GAMERS',
            'generation' => 'Gen 1 / GAMERS',
            'fanName' => 'Sukonbu',
            'themeColor' => '#38bdf8',
            'accentColor' => '#0284c7',
            'glowColor' => 'rgba(56, 189, 248, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shirakami-Fubuki_list_thumb.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shirakami-Fubuki_list_thumb.png',
            'quote' => '“Konkon Kitsune! Shirakami Fubuki desu! Friends, don’t let this limited flash drop slip away!”',
            'rarity' => 'MYTHIC',
        ],
        'nekomata-okayu' => [
            'japaneseName' => '猫又おかゆ',
            'unit' => 'hololive GAMERS',
            'generation' => 'GAMERS',
            'fanName' => 'Onigirya',
            'themeColor' => '#c084fc',
            'accentColor' => '#9333ea',
            'glowColor' => 'rgba(192, 132, 252, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/bg_Nekomata-Okayu_01.png',
            'prVisualAlt' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Nekomata-Okayu_pr-img_01.png',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Nekomata-Okayu_list_thumb.png',
            'quote' => '“Mogu mogu~ Okayu! Relax and treat yourself to this special flash drop before it’s all eaten up!”',
            'rarity' => 'LEGENDARY',
        ],
        'isaki-riona' => [
            'japaneseName' => '響咲リオナ',
            'unit' => 'hololive DEV_IS -FLOW GLOW-',
            'generation' => 'FLOW GLOW',
            'fanName' => 'Riona Fam',
            'themeColor' => '#f43f5e',
            'accentColor' => '#fb7185',
            'glowColor' => 'rgba(244, 63, 94, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Isaki-Riona_list_thumb.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Isaki-Riona_list_thumb.png',
            'quote' => '“FLOW GLOW’s leader Isaki Riona here! Turn the volume up and claim this flash drop before the encore!”',
            'rarity' => 'EPIC',
        ],
        'tsunomaki-watame' => [
            'japaneseName' => '角巻わため',
            'unit' => 'hololive 4th Gen',
            'generation' => 'Gen 4',
            'fanName' => 'Watamate',
            'themeColor' => '#fde047',
            'accentColor' => '#f59e0b',
            'glowColor' => 'rgba(253, 224, 71, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/bg_Tsunomaki-Watame_01.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tsunomaki-Watame_talent_list_thumb.png',
            'quote' => '“Konbandododo~! Watame did nothing wrong! Snag this cozy flash deal right now!”',
            'rarity' => 'LEGENDARY',
        ],
        'shiranui-flare' => [
            'japaneseName' => '不知火フレア',
            'unit' => 'hololive 3rd Gen (HoloFantasy)',
            'generation' => 'HoloFantasy 3期生',
            'fanName' => 'Elfriend',
            'themeColor' => '#f97316',
            'accentColor' => '#facc15',
            'glowColor' => 'rgba(249, 115, 22, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/bg_Shiranui-Flare_01.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shiranui-Flare_list_thumb.png',
            'quote' => '“Konnuigurumi~! Shiranui Flare desu! Let’s light up your collection with this flash deal!”',
            'rarity' => 'EPIC',
        ],
        'takanashi-kiara' => [
            'japaneseName' => '小鳥遊キアラ',
            'unit' => 'hololive English -Myth-',
            'generation' => 'HoloEN Myth',
            'fanName' => 'KFP',
            'themeColor' => '#f97316',
            'accentColor' => '#ef4444',
            'glowColor' => 'rgba(249, 115, 22, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/bg_Takanashi-Kiara_01.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Takanashi-Kiara_list_thumb.png',
            'quote' => '“Kikkeriki!! Takanashi Kiara here! Order up this fiery KFP flash drop right now!”',
            'rarity' => 'LEGENDARY',
        ],
        'ninomae-inanis' => [
            'japaneseName' => '一伊那尓栖',
            'unit' => 'hololive English -Myth-',
            'generation' => 'HoloEN Myth',
            'fanName' => 'Takodachi',
            'themeColor' => '#8b5cf6',
            'accentColor' => '#f59e0b',
            'glowColor' => 'rgba(139, 92, 246, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/bg_Ninomae-Inanis_01.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Ninomae-Inanis_list_thumb.png',
            'quote' => '“WAH! Ninomae Ina’nis desu~ We Are Having an incredible flash sale today, Takodachis!”',
            'rarity' => 'LEGENDARY',
        ],
        'watson-amelia' => [
            'japaneseName' => 'ワトソン・アメリア',
            'unit' => 'hololive English -Myth-',
            'generation' => 'HoloEN Myth',
            'fanName' => 'Teamates',
            'themeColor' => '#facc15',
            'accentColor' => '#38bdf8',
            'glowColor' => 'rgba(250, 204, 21, 0.45)',
            'standingVisual' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/bg_Watson-Amelia_01.png',
            'prVisualAlt' => '',
            'avatarThumb' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Watson-Amelia_list_thumb.png',
            'quote' => '“Hic! Detective Watson Amelia on the case—my investigation confirms this is the best deal in the timeline!”',
            'rarity' => 'LEGENDARY',
        ],
    ];

    if (isset($curated[$normalizedSlug])) {
        $entry = $curated[$normalizedSlug];
        if (!empty($talentImg) && empty($entry['avatarThumb'])) {
            $entry['avatarThumb'] = $talentImg;
        }
        return array_merge(['id' => $normalizedSlug, 'name' => $cleanName], $entry);
    }

    $jpNames = [
        'tokino-sora' => 'ときのそら',
        'roboco-san' => 'ロボ子さん',
        'azki' => 'AZKi',
        'aki-rosenthal' => 'アキ・ローゼンタール',
        'akai-haato' => '赤井はあと',
        'natsuiro-matsuri' => '夏色まつり',
        'ookami-mio' => '大神ミオ',
        'nakiri-ayame' => '百鬼あやめ',
        'yuzuki-choco' => '癒月ちょこ',
        'oozora-subaru' => '大空スバル',
        'shirogane-noel' => '白銀ノエル',
        'tokoyami-towa' => '常闇トワ',
        'himemori-luna' => '姫森ルーナ',
        'yukihana-lamy' => '雪花ラミィ',
        'momosuzu-nene' => '桃鈴ねね',
        'shishiro-botan' => '獅白ぼたん',
        'omaru-polka' => '尾丸ポルカ',
        'la-darknesss' => 'ラプラス・ダークネス',
        'takane-lui' => '鷹嶺ルイ',
        'kazama-iroha' => '風真いろは',
        'otonose-kanade' => '音乃瀬奏',
        'ichijou-ririka' => '一条莉々華',
        'juufuutei-raden' => '儒烏風亭らでん',
        'todoroki-hajime' => '轟はじめ',
        'koganei-niko' => '虎金妃笑虎',
        'mizumiya-su' => '水宮枢',
        'rindo-chihaya' => '輪堂千速',
        'kikirara-vivi' => '綺々羅々ヴィヴィ',
        'irys' => 'アイリス',
        'ouro-kronii' => 'オーロ・クロニー',
        'hakos-baelz' => 'ハコス・ベールズ',
        'shiori-novella' => 'シオリ・ノヴェラ',
        'koseki-bijou' => '古石ビジュー',
        'nerissa-ravencroft' => 'ネリッサ・レイヴンクロフト',
        'fuwawa-abyssgard' => 'フワワ・アビスガード',
        'mococo-abyssgard' => 'モココ・アビスガード',
        'elizabeth-rose-bloodflame' => 'エリザベス・R・ブラッドフレイム',
        'gigi-murin' => 'ジジ・ムリン',
        'cecilia-immergreen' => 'セシリア・イマーグリーン',
        'raora-panthera' => 'ラオーラ・パンテーラ',
        'ayunda-risu' => 'アユンダ・リス',
        'moona-hoshinova' => 'ムーナ・ホシノヴァ',
        'airani-iofifteen' => 'アイラニ・イオフィフティーン',
        'kureiji-ollie' => 'クレイジー・オリー',
        'anya-melfissa' => 'アーニャ・メルフィッサ',
        'pavolia-reine' => 'パヴォリア・レイネ',
        'vestia-zeta' => 'ベスティア・ゼータ',
        'kaela-kovalskia' => 'カエラ・コヴァルスキア',
        'kobo-kanaeru' => 'こぼ・かなえる',
    ];

    $palettes = [
        ['theme' => '#00c3ff', 'accent' => '#1e40af', 'glow' => 'rgba(0, 195, 255, 0.45)', 'rarity' => 'MYTHIC'],
        ['theme' => '#ff2a6d', 'accent' => '#fbbf24', 'glow' => 'rgba(255, 42, 109, 0.45)', 'rarity' => 'LEGENDARY'],
        ['theme' => '#6366f1', 'accent' => '#06b6d4', 'glow' => 'rgba(99, 102, 241, 0.45)', 'rarity' => 'LEGENDARY'],
        ['theme' => '#ec4899', 'accent' => '#a855f7', 'glow' => 'rgba(236, 72, 153, 0.45)', 'rarity' => 'EPIC'],
        ['theme' => '#f59e0b', 'accent' => '#ef4444', 'glow' => 'rgba(245, 158, 11, 0.45)', 'rarity' => 'LEGENDARY'],
        ['theme' => '#2dd4bf', 'accent' => '#3b82f6', 'glow' => 'rgba(45, 212, 191, 0.45)', 'rarity' => 'EPIC'],
    ];
    $palette = $palettes[abs(crc32($normalizedSlug)) % count($palettes)];
    $resolvedThumb = !empty($talentImg)
        ? $talentImg
        : 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Hoshimachi-Suisei_list_thumb.png';
    $unitLabel = !empty($groupName) ? 'hololive ' . $groupName : 'hololive Official Production';
    $genLabel = !empty($groupName) ? $groupName : 'Official Drop';

    return [
        'id' => $normalizedSlug ?: 'hololive-official',
        'name' => $cleanName,
        'japaneseName' => $jpNames[$normalizedSlug] ?? 'ホロライブ公式',
        'unit' => $unitLabel,
        'generation' => $genLabel,
        'fanName' => $cleanName . ' Official Collectors',
        'themeColor' => $palette['theme'],
        'accentColor' => $palette['accent'],
        'glowColor' => $palette['glow'],
        'standingVisual' => $resolvedThumb,
        'prVisualAlt' => '',
        'avatarThumb' => $resolvedThumb,
        'quote' => '“Welcome to the official HoloVerse Flash Vault! Claim this limited-allocation ' . $cleanName . ' collector drop before the timer hits zero!”',
        'rarity' => $palette['rarity'],
    ];
}

function store_flash_sale_build_selection_data(
    array $product,
    string $state,
    array $productImages,
    DateTimeZone $timezone,
    string $siteRoot = '../'
): array {
    $stock = max(0, (int) ($product['stock_quantity'] ?? 0));
    $capacity = max(24, $stock + 14);
    $claimedPercent = $stock > 0
        ? min(96, max(14, (int) round((($capacity - $stock) / $capacity) * 100)))
        : 100;
    $price = (float) ($product['price'] ?? 0);
    $comparePrice = !empty($product['compare_at_price']) && (float) $product['compare_at_price'] > $price
        ? (float) $product['compare_at_price']
        : round($price * 1.2, 2);
    $discountPercent = $comparePrice > 0 ? (int) round((1 - ($price / $comparePrice)) * 100) : 0;

    if (!$productImages) {
        $productImages = [[
            'image_url' => store_asset_url((string) ($product['image_url'] ?? 'assets/images/logo.png'), $siteRoot),
            'alt_text' => (string) ($product['alt_text'] ?? $product['product_name'] ?? 'Official Merch'),
        ]];
    }

    $productTalent = (string) (!empty($product['talent_name'])
        ? $product['talent_name']
        : ($product['category_name'] ?? 'hololive production'));
    $talentSlug = (string) ($product['talent_slug'] ?? '');
    $talentImg = !empty($product['talent_img'])
        ? store_asset_url((string) $product['talent_img'], $siteRoot)
        : null;
    $talentGroup = isset($product['talent_group']) ? (string) $product['talent_group'] : null;
    $talentMeta = store_flash_sale_talent_meta($talentSlug, $productTalent, $talentImg, $talentGroup);

    $saleStartLabel = !empty($product['starts_at'])
        ? (new DateTimeImmutable($product['starts_at'], $timezone))->format('M j, g:i a')
        : '';
    $startsAtIso = !empty($product['starts_at'])
        ? (new DateTimeImmutable($product['starts_at'], $timezone))->format(DateTimeInterface::ATOM)
        : '';
    $endsAtIso = !empty($product['ends_at'])
        ? (new DateTimeImmutable($product['ends_at'], $timezone))->format(DateTimeInterface::ATOM)
        : '';

    $badgeLabel = $discountPercent > 0
        ? "{$discountPercent}% OFF FLASH DROP"
        : 'LIMITED FLASH DROP';
    if ($state === 'archive') {
        $badgeLabel = 'VAULT ARCHIVE DROP';
    } elseif ($state !== 'live') {
        $badgeLabel = $discountPercent > 0 ? "{$discountPercent}% OFF UPCOMING DROP" : 'UPCOMING FLASH DROP';
    }

    $approxUsd = '$' . number_format(max(1, round($price * (STORE_CURRENCY_RATES['USD'] ?? 0.017))), 0);
    $approxJpy = '¥' . number_format(max(100, round($price * (STORE_CURRENCY_RATES['JPY'] ?? 2.64))), 0);
    $savings = max(0, $comparePrice - $price);

    return [
        'name' => (string) ($product['product_name'] ?? ''),
        'description' => (string) ($product['description'] ?? ''),
        'talent' => $talentMeta['name'],
        'talentSlug' => $talentMeta['id'],
        'talentAvatar' => $talentMeta['avatarThumb'] ?? $talentImg ?? '',
        'japaneseName' => $talentMeta['japaneseName'],
        'unit' => $talentMeta['unit'],
        'generation' => $talentMeta['generation'],
        'fanName' => $talentMeta['fanName'],
        'themeColor' => $talentMeta['themeColor'],
        'accentColor' => $talentMeta['accentColor'],
        'glowColor' => $talentMeta['glowColor'],
        'standingVisual' => $talentMeta['standingVisual'],
        'prVisualAlt' => $talentMeta['prVisualAlt'],
        'avatarThumb' => $talentMeta['avatarThumb'],
        'quote' => $talentMeta['quote'],
        'rarity' => $talentMeta['rarity'],
        'badge' => $badgeLabel,
        'category' => (string) ($product['category_name'] ?? 'Official Merch'),
        'variant' => (string) ($product['variant_name'] ?? ''),
        'variantName' => (string) ($product['variant_name'] ?? ''),
        'stock' => $stock,
        'capacity' => $capacity,
        'claimedPercent' => $claimedPercent,
        'price' => store_format_price($price),
        'salePriceFormatted' => store_format_price($price),
        'comparePrice' => store_format_price($comparePrice),
        'originalPriceFormatted' => store_format_price($comparePrice),
        'savings' => store_format_price($savings),
        'savingsFormatted' => store_format_price($savings),
        'approxUsd' => $approxUsd,
        'approxJpy' => $approxJpy,
        'discount' => $discountPercent,
        'state' => $state,
        'variantId' => (int) ($product['variant_id'] ?? 0),
        'startsLabel' => $saleStartLabel,
        'startsAt' => $startsAtIso,
        'endsAt' => $endsAtIso,
        'images' => $productImages,
        'productUrl' => store_product_url((string) $product['product_name']),
    ];
}
