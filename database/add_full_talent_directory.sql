START TRANSACTION;

INSERT INTO `talent_group_tbl` (`group_name`, `group_slug`)
SELECT source.`group_name`, source.`group_slug`
FROM (
    SELECT 'Generation 0' AS `group_name`, 'gen-0' AS `group_slug`
    UNION ALL SELECT 'Generation 1', 'gen-1'
    UNION ALL SELECT 'Gamers', 'gamers'
    UNION ALL SELECT 'Generation 2', 'gen-2'
    UNION ALL SELECT 'Generation 3', 'gen-3'
    UNION ALL SELECT 'Generation 4', 'gen-4'
    UNION ALL SELECT 'Generation 5', 'gen-5'
    UNION ALL SELECT 'HoloX', 'holox'
    UNION ALL SELECT 'ReGLOSS', 'regloss'
    UNION ALL SELECT 'FLOW GLOW', 'flow-glow'
    UNION ALL SELECT 'Myth', 'myth'
    UNION ALL SELECT 'Promise', 'promise'
    UNION ALL SELECT 'Advent', 'advent'
    UNION ALL SELECT 'Justice', 'justice'
    UNION ALL SELECT 'Indonesia Gen 1', 'id-gen-1'
    UNION ALL SELECT 'Indonesia Gen 2', 'id-gen-2'
    UNION ALL SELECT 'Indonesia Gen 3', 'id-gen-3'
    UNION ALL SELECT 'Asobi Mawaritai', 'asobi-mawaritai'
) AS source
WHERE NOT EXISTS (
    SELECT 1 FROM `talent_group_tbl` existing
    WHERE existing.`group_slug` = source.`group_slug`
);

INSERT INTO `talent_tbl` (`talent_name`, `talent_slug`, `talent_img`)
SELECT source.`talent_name`, source.`talent_slug`, source.`talent_img`
FROM (
    SELECT 'Tokino Sora' AS `talent_name`, 'tokino-sora' AS `talent_slug`, 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tokino-Sora_list_thumb.png' AS `talent_img`
    UNION ALL SELECT 'Roboco-san', 'roboco-san', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Robocosan_list_thumb.png'
    UNION ALL SELECT 'AZKi', 'azki', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/AZKi_list_thumb.png'
    UNION ALL SELECT 'Sakura Miko', 'sakura-miko', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Sakura-Miko_list_thumb.png'
    UNION ALL SELECT 'Hoshimachi Suisei', 'hoshimachi-suisei', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Hoshimachi-Suisei_list_thumb.png'
    UNION ALL SELECT 'Aki Rosenthal', 'aki-rosenthal', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Aki-Rosenthal_list_thumb.png'
    UNION ALL SELECT 'Akai Haato', 'akai-haato', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Akai-Haato_list_thumb.png'
    UNION ALL SELECT 'Shirakami Fubuki', 'shirakami-fubuki', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shirakami-Fubuki_list_thumb.png'
    UNION ALL SELECT 'Natsuiro Matsuri', 'natsuiro-matsuri', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Natsuiro-Matsuri_list_thumb.png'
    UNION ALL SELECT 'Ookami Mio', 'ookami-mio', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Ookami-Mio_thumb.png'
    UNION ALL SELECT 'Nekomata Okayu', 'nekomata-okayu', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Nekomata-Okayu_list_thumb.png'
    UNION ALL SELECT 'Inugami Korone', 'inugami-korone', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Inugami-Korone_list_thumb.png'
    UNION ALL SELECT 'Nakiri Ayame', 'nakiri-ayame', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Nakiri-Ayame_list_thumb.png'
    UNION ALL SELECT 'Yuzuki Choco', 'yuzuki-choco', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Yuzuki-Choco_list_thumb.png'
    UNION ALL SELECT 'Oozora Subaru', 'oozora-subaru', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Oozora-Subaru_list_thumb.png'
    UNION ALL SELECT 'Gawr Gura', 'gawr-gura', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Gawr-Gura_list_thumb.png'
    UNION ALL SELECT 'Usada Pekora', 'usada-pekora', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Usada-Pekora_list_thumb.png'
    UNION ALL SELECT 'Shiranui Flare', 'shiranui-flare', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shiranui-Flare_list_thumb.png'
    UNION ALL SELECT 'Shirogane Noel', 'shirogane-noel', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Shirogane-Noel_list_thumb.png'
    UNION ALL SELECT 'Houshou Marine', 'houshou-marine', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Houshou-Marine_list_thumb.png'
    UNION ALL SELECT 'Tsunomaki Watame', 'tsunomaki-watame', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tsunomaki-Watame_talent_list_thumb.png'
    UNION ALL SELECT 'Tokoyami Towa', 'tokoyami-towa', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tokoyami-Towa_list_thumb.png'
    UNION ALL SELECT 'Himemori Luna', 'himemori-luna', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Himemori-Luna_list_thumb.png'
    UNION ALL SELECT 'Yukihana Lamy', 'yukihana-lamy', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Yukihana-Lamy_list_thumb.png'
    UNION ALL SELECT 'Momosuzu Nene', 'momosuzu-nene', 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Momosuzu-Nene_talent_list_thumb.png'
    UNION ALL SELECT 'Shishiro Botan', 'shishiro-botan', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Shishiro-Botan_list_thumb.png'
    UNION ALL SELECT 'Omaru Polka', 'omaru-polka', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Omaru-Polka_list_thumb.png'
    UNION ALL SELECT 'La+ Darknesss', 'la-darknesss', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/La-Darknesss_list_thumb.png'
    UNION ALL SELECT 'Takane Lui', 'takane-lui', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Takane-Lui_list_thumb.png'
    UNION ALL SELECT 'Hakui Koyori', 'hakui-koyori', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Hakui-Koyori_list_thumb.png'
    UNION ALL SELECT 'Kazama Iroha', 'kazama-iroha', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kazama-Iroha_talent_list_thumb.png'
    UNION ALL SELECT 'Otonose Kanade', 'otonose-kanade', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Otonose-Kanade_list_thumb.png'
    UNION ALL SELECT 'Ichijou Ririka', 'ichijou-ririka', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Ichijou-Ririka_list_thumb.png'
    UNION ALL SELECT 'Juufuutei Raden', 'juufuutei-raden', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Juufuutei-Raden_list_thumb.png'
    UNION ALL SELECT 'Todoroki Hajime', 'todoroki-hajime', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Todoroki-Hajime_list_thumb.png'
    UNION ALL SELECT 'Isaki Riona', 'isaki-riona', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Isaki-Riona_list_thumb.png'
    UNION ALL SELECT 'Koganei Niko', 'koganei-niko', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Koganei-Niko_list_thumb.png'
    UNION ALL SELECT 'Mizumiya Su', 'mizumiya-su', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Mizumiya-Su_list_thumb.png'
    UNION ALL SELECT 'Rindo Chihaya', 'rindo-chihaya', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Rindo-Chihaya_list_thumb.png'
    UNION ALL SELECT 'Kikirara Vivi', 'kikirara-vivi', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Kikirara-Vivi_list_thumb.png'
    UNION ALL SELECT 'Mori Calliope', 'mori-calliope', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Mori-Calliope_list_thumb.png'
    UNION ALL SELECT 'Takanashi Kiara', 'takanashi-kiara', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Takanashi-Kiara_list_thumb.png'
    UNION ALL SELECT 'Ninomae Ina’nis', 'ninomae-inanis', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Ninomae-Inanis_list_thumb.png'
    UNION ALL SELECT 'Watson Amelia', 'watson-amelia', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Watson-Amelia_list_thumb.png'
    UNION ALL SELECT 'IRyS', 'irys', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/IRyS_list_thumb.png'
    UNION ALL SELECT 'Ouro Kronii', 'ouro-kronii', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Ouro-Kronii_list_thumb.png'
    UNION ALL SELECT 'Hakos Baelz', 'hakos-baelz', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Hakos-Baelz_list_thumb.png'
    UNION ALL SELECT 'Shiori Novella', 'shiori-novella', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Shiori-Novella_list_thumb.png'
    UNION ALL SELECT 'Koseki Bijou', 'koseki-bijou', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Koseki-Bijou_list_thumb.png'
    UNION ALL SELECT 'Nerissa Ravencroft', 'nerissa-ravencroft', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Nerissa-Ravencroft_list_thumb.png'
    UNION ALL SELECT 'Fuwawa Abyssgard', 'fuwawa-abyssgard', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Fuwawa-Abyssgard_list_thumb.png'
    UNION ALL SELECT 'Mococo Abyssgard', 'mococo-abyssgard', 'https://hololive.hololivepro.com/wp-content/uploads/2021/07/Mococo-Abyssgard_list_thumb.png'
    UNION ALL SELECT 'Elizabeth Rose Bloodflame', 'elizabeth-rose-bloodflame', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Elizabeth-Rose-Bloodflame_list_thumb.png'
    UNION ALL SELECT 'Gigi Murin', 'gigi-murin', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Gigi-Murin_list_thumb.png'
    UNION ALL SELECT 'Cecilia Immergreen', 'cecilia-immergreen', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Cecilia-Immergreen_list_thumb.png'
    UNION ALL SELECT 'Raora Panthera', 'raora-panthera', 'https://hololive.hololivepro.com/wp-content/uploads/2023/07/Raora-Panthera_list_thumb.png'
    UNION ALL SELECT 'Ayunda Risu', 'ayunda-risu', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Ayunda-Risu_list_thumb.png'
    UNION ALL SELECT 'Moona Hoshinova', 'moona-hoshinova', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Moona-Hoshinova_list_thumb.png'
    UNION ALL SELECT 'Airani Iofifteen', 'airani-iofifteen', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Airani-Iofifteen_list_thumb.png'
    UNION ALL SELECT 'Kureiji Ollie', 'kureiji-ollie', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kureiji-Ollie_list_thumb.png'
    UNION ALL SELECT 'Anya Melfissa', 'anya-melfissa', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Anya-Melfissa_list_thumb.png'
    UNION ALL SELECT 'Pavolia Reine', 'pavolia-reine', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Pavolia-Reine_list_thumb.png'
    UNION ALL SELECT 'Vestia Zeta', 'vestia-zeta', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Vestia-Zeta_list_thumb.png'
    UNION ALL SELECT 'Kaela Kovalskia', 'kaela-kovalskia', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kaela-Kovalskia_list_thumb.png'
    UNION ALL SELECT 'Kobo Kanaeru', 'kobo-kanaeru', 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kobo-Kanaeru_list_thumb.png'
    UNION ALL SELECT 'Hyakuto Kyoko', 'hyakuto-kyoko', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Hyakuto-Kyoko_list_thumb.png'
    UNION ALL SELECT 'Achichi Mela', 'achichi-mela', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Achichi-Mela_list_thumb.png'
    UNION ALL SELECT 'Suzuna Tsuzuri', 'suzuna-tsuzuri', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Suzuna-Tsuzuri_list_thumb.png'
    UNION ALL SELECT 'Sorashina Sopia', 'sorashina-sopia', 'https://hololive.hololivepro.com/wp-content/uploads/2023/09/Sorashina-Sopia_list_thumb.png'
) AS source
WHERE NOT EXISTS (
    SELECT 1 FROM `talent_tbl` existing
    WHERE existing.`talent_slug` = source.`talent_slug`
       OR existing.`talent_name` = source.`talent_name`
);

UPDATE `talent_tbl`
SET `talent_img` = CASE
    WHEN `talent_slug` = 'tsunomaki-watame' OR `talent_name` = 'Tsunomaki Watame'
        THEN 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tsunomaki-Watame_talent_list_thumb.png'
    WHEN `talent_slug` = 'momosuzu-nene' OR `talent_name` = 'Momosuzu Nene'
        THEN 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Momosuzu-Nene_talent_list_thumb.png'
    WHEN `talent_slug` = 'kazama-iroha' OR `talent_name` = 'Kazama Iroha'
        THEN 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kazama-Iroha_talent_list_thumb.png'
END
WHERE `talent_slug` IN ('tsunomaki-watame', 'momosuzu-nene', 'kazama-iroha')
   OR `talent_name` IN ('Tsunomaki Watame', 'Momosuzu Nene', 'Kazama Iroha');

INSERT INTO `talent_group_members_tbl` (`talent_id`, `group_id`)
SELECT talent.`talent_id`, talent_group.`group_id`
FROM (
    SELECT 'tokino-sora' AS `talent_slug`, 'gen-0' AS `group_slug`
    UNION ALL SELECT 'roboco-san', 'gen-0'
    UNION ALL SELECT 'azki', 'gen-0'
    UNION ALL SELECT 'sakura-miko', 'gen-0'
    UNION ALL SELECT 'hoshimachi-suisei', 'gen-0'
    UNION ALL SELECT 'aki-rosenthal', 'gen-1'
    UNION ALL SELECT 'akai-haato', 'gen-1'
    UNION ALL SELECT 'shirakami-fubuki', 'gen-1'
    UNION ALL SELECT 'natsuiro-matsuri', 'gen-1'
    UNION ALL SELECT 'shirakami-fubuki', 'gamers'
    UNION ALL SELECT 'ookami-mio', 'gamers'
    UNION ALL SELECT 'nekomata-okayu', 'gamers'
    UNION ALL SELECT 'inugami-korone', 'gamers'
    UNION ALL SELECT 'nakiri-ayame', 'gen-2'
    UNION ALL SELECT 'yuzuki-choco', 'gen-2'
    UNION ALL SELECT 'oozora-subaru', 'gen-2'
    UNION ALL SELECT 'usada-pekora', 'gen-3'
    UNION ALL SELECT 'shiranui-flare', 'gen-3'
    UNION ALL SELECT 'shirogane-noel', 'gen-3'
    UNION ALL SELECT 'houshou-marine', 'gen-3'
    UNION ALL SELECT 'tsunomaki-watame', 'gen-4'
    UNION ALL SELECT 'tokoyami-towa', 'gen-4'
    UNION ALL SELECT 'himemori-luna', 'gen-4'
    UNION ALL SELECT 'yukihana-lamy', 'gen-5'
    UNION ALL SELECT 'momosuzu-nene', 'gen-5'
    UNION ALL SELECT 'shishiro-botan', 'gen-5'
    UNION ALL SELECT 'omaru-polka', 'gen-5'
    UNION ALL SELECT 'la-darknesss', 'holox'
    UNION ALL SELECT 'takane-lui', 'holox'
    UNION ALL SELECT 'hakui-koyori', 'holox'
    UNION ALL SELECT 'kazama-iroha', 'holox'
    UNION ALL SELECT 'otonose-kanade', 'regloss'
    UNION ALL SELECT 'ichijou-ririka', 'regloss'
    UNION ALL SELECT 'juufuutei-raden', 'regloss'
    UNION ALL SELECT 'todoroki-hajime', 'regloss'
    UNION ALL SELECT 'isaki-riona', 'flow-glow'
    UNION ALL SELECT 'koganei-niko', 'flow-glow'
    UNION ALL SELECT 'mizumiya-su', 'flow-glow'
    UNION ALL SELECT 'rindo-chihaya', 'flow-glow'
    UNION ALL SELECT 'kikirara-vivi', 'flow-glow'
    UNION ALL SELECT 'mori-calliope', 'myth'
    UNION ALL SELECT 'takanashi-kiara', 'myth'
    UNION ALL SELECT 'ninomae-inanis', 'myth'
    UNION ALL SELECT 'watson-amelia', 'myth'
    UNION ALL SELECT 'gawr-gura', 'myth'
    UNION ALL SELECT 'irys', 'promise'
    UNION ALL SELECT 'ouro-kronii', 'promise'
    UNION ALL SELECT 'hakos-baelz', 'promise'
    UNION ALL SELECT 'shiori-novella', 'advent'
    UNION ALL SELECT 'koseki-bijou', 'advent'
    UNION ALL SELECT 'nerissa-ravencroft', 'advent'
    UNION ALL SELECT 'fuwawa-abyssgard', 'advent'
    UNION ALL SELECT 'mococo-abyssgard', 'advent'
    UNION ALL SELECT 'elizabeth-rose-bloodflame', 'justice'
    UNION ALL SELECT 'gigi-murin', 'justice'
    UNION ALL SELECT 'cecilia-immergreen', 'justice'
    UNION ALL SELECT 'raora-panthera', 'justice'
    UNION ALL SELECT 'ayunda-risu', 'id-gen-1'
    UNION ALL SELECT 'moona-hoshinova', 'id-gen-1'
    UNION ALL SELECT 'airani-iofifteen', 'id-gen-1'
    UNION ALL SELECT 'kureiji-ollie', 'id-gen-2'
    UNION ALL SELECT 'anya-melfissa', 'id-gen-2'
    UNION ALL SELECT 'pavolia-reine', 'id-gen-2'
    UNION ALL SELECT 'vestia-zeta', 'id-gen-3'
    UNION ALL SELECT 'kaela-kovalskia', 'id-gen-3'
    UNION ALL SELECT 'kobo-kanaeru', 'id-gen-3'
    UNION ALL SELECT 'hyakuto-kyoko', 'asobi-mawaritai'
    UNION ALL SELECT 'achichi-mela', 'asobi-mawaritai'
    UNION ALL SELECT 'suzuna-tsuzuri', 'asobi-mawaritai'
    UNION ALL SELECT 'sorashina-sopia', 'asobi-mawaritai'
) AS source
INNER JOIN `talent_tbl` talent ON talent.`talent_slug` = source.`talent_slug`
INNER JOIN `talent_group_tbl` talent_group ON talent_group.`group_slug` = source.`group_slug`
LEFT JOIN `talent_group_members_tbl` existing
    ON existing.`talent_id` = talent.`talent_id`
   AND existing.`group_id` = talent_group.`group_id`
WHERE existing.`talent_id` IS NULL;

COMMIT;
