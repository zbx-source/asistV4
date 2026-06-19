-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 19 Haz 2026, 08:36:40
-- Sunucu sürümü: 10.5.29-MariaDB
-- PHP Sürümü: 8.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `zbasist`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `addons`
--

CREATE TABLE `addons` (
  `id` int(11) NOT NULL,
  `code` varchar(60) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` enum('per_user','per_module','flat') NOT NULL DEFAULT 'flat',
  `price_usd` decimal(8,2) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('super_admin','operator') NOT NULL DEFAULT 'operator',
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `admin_users`
--

INSERT INTO `admin_users` (`id`, `name`, `email`, `password_hash`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@zboxasist.com', '$2y$10$GDhUMlQ3g4L03P82RLRZYuf6UIGxP/SEkH7lusl6XWbk0MHma76iu', 'super_admin', 'active', '2026-06-10 11:12:56', '2026-06-10 11:24:15');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ai_usage_log`
--

CREATE TABLE `ai_usage_log` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `conversation_id` bigint(20) DEFAULT NULL,
  `type` enum('chat','summary') NOT NULL DEFAULT 'chat',
  `model` varchar(60) DEFAULT NULL,
  `prompt_tokens` int(11) NOT NULL DEFAULT 0,
  `completion_tokens` int(11) NOT NULL DEFAULT 0,
  `total_tokens` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `ai_usage_log`
--

INSERT INTO `ai_usage_log` (`id`, `client_id`, `conversation_id`, `type`, `model`, `prompt_tokens`, `completion_tokens`, `total_tokens`, `created_at`) VALUES
(1, 1, 4, 'chat', 'gpt-5.4-mini', 1070, 102, 1172, '2026-06-15 11:28:02'),
(2, 1, 4, 'summary', 'gpt-5.4-mini', 1112, 88, 1200, '2026-06-15 11:32:30'),
(3, 1, 4, 'chat', 'gpt-5.4-mini', 1084, 98, 1182, '2026-06-15 11:40:47'),
(4, 1, 4, 'summary', 'gpt-5.4-mini', 1288, 105, 1393, '2026-06-15 11:41:48');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `clients`
--

CREATE TABLE `clients` (
  `id` bigint(20) NOT NULL,
  `type` enum('clinic','agency') NOT NULL,
  `name` varchar(180) NOT NULL,
  `license_no` varchar(80) DEFAULT NULL,
  `contact_name` varchar(120) DEFAULT NULL,
  `authorized_person_1` varchar(120) DEFAULT NULL,
  `authorized_person_2` varchar(120) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `phone_2` varchar(20) DEFAULT NULL,
  `contact_email` varchar(120) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_no` varchar(20) DEFAULT NULL,
  `tax_office` varchar(120) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `country` varchar(80) DEFAULT 'TR',
  `status` enum('active','suspended','cancelled') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `clients`
--

INSERT INTO `clients` (`id`, `type`, `name`, `license_no`, `contact_name`, `authorized_person_1`, `authorized_person_2`, `contact_phone`, `phone_2`, `contact_email`, `address`, `tax_no`, `tax_office`, `city`, `country`, `status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'clinic', 'Zbox STEP', '6545465454', NULL, 'Ozan İLTER', '', '5323439573', '', 'bilgi@zboxstep.com', 'Teknopark İzmir A-8 Blok No 22 Urla', '0680567425', '', 'İzmir', 'TR', 'active', 'ekip pakete dönebilir !', 1, '2026-06-10 13:37:20', '2026-06-14 15:37:57');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `client_addons`
--

CREATE TABLE `client_addons` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `status` enum('active','cancelled') NOT NULL DEFAULT 'active',
  `activated_by` int(11) DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `client_features`
--

CREATE TABLE `client_features` (
  `client_id` bigint(20) NOT NULL,
  `feature_code` varchar(60) NOT NULL,
  `state` enum('on','off') NOT NULL DEFAULT 'on',
  `enabled_by` int(11) DEFAULT NULL,
  `enabled_at` datetime DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `client_tokens`
--

CREATE TABLE `client_tokens` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `token` varchar(64) NOT NULL,
  `whatsapp_number` varchar(20) NOT NULL,
  `phone_number_id` varchar(40) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `client_tokens`
--

INSERT INTO `client_tokens` (`id`, `client_id`, `token`, `whatsapp_number`, `phone_number_id`, `status`, `created_at`) VALUES
(1, 1, 'zb_test_token_001', '+905321234567', '1239638309231513', 'active', '2026-06-11 13:26:25');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `conversations`
--

CREATE TABLE `conversations` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `patient_id` bigint(20) NOT NULL,
  `module_id` bigint(20) DEFAULT NULL,
  `status` enum('ai_active','pending_takeover','assigned','with_user','closed') NOT NULL DEFAULT 'ai_active',
  `topic_summary` varchar(255) DEFAULT NULL COMMENT 'AI tespit: implant-fiyat bilgisi, saç ekimi-fotoğraf istendi vs.',
  `summary_text` text DEFAULT NULL COMMENT 'AI konuşma özeti',
  `summary_last_msg_id` bigint(20) DEFAULT NULL COMMENT 'Özet çıkarıldığındaki son mesaj ID',
  `assigned_to` bigint(20) DEFAULT NULL,
  `assigned_by` bigint(20) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `taken_over_at` datetime DEFAULT NULL,
  `template_sent` tinyint(1) NOT NULL DEFAULT 0,
  `template_replied` tinyint(1) NOT NULL DEFAULT 0,
  `closed_at` datetime DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `conversations`
--

INSERT INTO `conversations` (`id`, `client_id`, `patient_id`, `module_id`, `status`, `topic_summary`, `summary_text`, `summary_last_msg_id`, `assigned_to`, `assigned_by`, `assigned_at`, `taken_over_at`, `template_sent`, `template_replied`, `closed_at`, `started_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 'ai_active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, '2026-06-11 15:04:00', '2026-06-11 15:04:00'),
(2, 1, 2, NULL, 'ai_active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, '2026-06-11 16:28:15', '2026-06-11 16:56:23'),
(3, 1, 3, NULL, 'ai_active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, '2026-06-11 16:58:26', '2026-06-11 16:58:26'),
(4, 1, 4, NULL, 'with_user', 'diş implantı - fotoğraf bekleniyor', 'Hasta diş implantı yaptırıp yaptırmadıklarını sordu, fiyat bilgisini öğrenmek istedi ve röntgeni olmadığı için kaç doktorla çalışıldığını da merak etti. Son olarak gülüş tasarımı hakkında da bilgi ve fiyat sordu; süreçte genel bilgilendirme yapıldı ancak net fiyat ya da değerlendirme için fotoğraf istenerek bekleme aşamasında kaldı.', 36, 1, NULL, NULL, '2026-06-17 08:30:49', 0, 0, NULL, '2026-06-12 18:18:40', '2026-06-17 08:30:49');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `core_rules`
--

CREATE TABLE `core_rules` (
  `id` int(11) NOT NULL,
  `version` varchar(20) NOT NULL,
  `content` mediumtext NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `core_rules`
--

INSERT INTO `core_rules` (`id`, `version`, `content`, `status`, `created_by`, `created_at`) VALUES
(1, '1.0', 'Sen Zbox Asist\'sin. Sağlık turizmi kliniklerine gelen hastaları karşılayan dijital bir hasta koordinatörüsün. Kibarca ve profesyonelce yanıt ver. Medikal teşhis veya tedavi önerisi yapma.', 'archived', 1, '2026-06-11 16:27:47'),
(2, '1.1', 'Sen Zbox Asist\'sin. Sağlık turizmi kliniklerine gelen uluslararası hastaları karşılayan dijital bir hasta koordinatörüsün.\r\n\r\nDEĞIŞMEZ KURALLAR:\r\n- Hastanın dilinde yanıt ver. Hangi dilde yazarsa o dilde devam et.\r\n- Medikal teşhis veya tedavi önerisi YAPMA.\r\n- Tıbbi görselleri (röntgen, fotoğraf) YORUMLAMA, sadece alındığını bildir.\r\n- Klinik prompt\'undaki kurallara uy. Orada fiyat ver deniyorsa ver, verilmesin deniyorsa verme.\r\n\r\nINTERNAL METADATA (hasta bu satırı GÖRMEZ):\r\nHer yanıtının en sonuna şu formatta bir satır ekle:\r\n|||META:{\"treatment\":\"...\",\"lang\":\"...\",\"country\":\"...\",\"topic\":\"...\",\"pipeline\":\"...\"}|||\r\n\r\nAlanlar:\r\n- treatment: hastanın ilgilendiği tedavi (implant, hollywood smile, hair transplant, rhinoplasty vs.) Kısa İngilizce terim.\r\n- lang: hastanın dili ISO 639-1 (ar, fr, en, de, ru, fa vs.)\r\n- country: hastanın ülkesi ISO 3166-1 alpha-2 (SA, IQ, FR vs.)\r\n- topic: konuşma özeti \"tedavi - aşama\" formatında (örn: \"implant - price inquiry\", \"hair transplant - photo sent\")\r\n- pipeline: hastanın süreçteki yeri: \"new\", \"photo_pending\", \"price_given\", \"followup\", \"won\", \"lost\"\r\n\r\nBu satırı HER yanıtta ekle. Belirleyemediğin alan için \"unknown\" yaz.', 'archived', 1, '2026-06-15 09:56:13'),
(3, '1.2', 'Sen Zbox Asist\'sin. Sağlık turizmi kliniklerine gelen uluslararası hastaları karşılayan dijital bir hasta koordinatörüsün.\r\n\r\nDEĞIŞMEZ KURALLAR:\r\n- Hastanın dilinde yanıt ver. Hangi dilde yazarsa o dilde devam et.\r\n- Medikal teşhis veya tedavi önerisi YAPMA.\r\n- Tıbbi görselleri (röntgen, fotoğraf) YORUMLAMA, sadece alındığını bildir.\r\n- Klinik prompt\'undaki kurallara uy. Orada fiyat ver deniyorsa ver, verilmesin deniyorsa verme.\r\n\r\nINTERNAL METADATA (hasta bu satırı GÖRMEZ):\r\nHer yanıtının en sonuna şu formatta bir satır ekle:\r\n|||META:{\"treatment\":\"...\",\"lang\":\"...\",\"country\":\"...\",\"topic\":\"...\",\"pipeline\":\"...\"}|||\r\n\r\nAlanlar:\r\n- treatment: hastanın ilgilendiği tedavi (implant, hollywood smile, hair transplant, rhinoplasty vs.) Kısa İngilizce terim.\r\n- lang: hastanın dili ISO 639-1 (ar, fr, en, de, ru, fa vs.)\r\n- country: hastanın ülkesi ISO 3166-1 alpha-2 (SA, IQ, FR vs.)\r\n- topic: konuşma özeti \"tedavi - aşama\" formatında (örn: \"implant - price inquiry\", \"hair transplant - photo sent\")\r\n- pipeline: hastanın süreçteki yeri: \"new\", \"photo_pending\", \"price_given\", \"followup\", \"won\", \"lost\"\r\n\r\nBu satırı HER yanıtta ekle. Belirleyemediğin alan için \"unknown\" yaz.', 'archived', 1, '2026-06-15 10:45:13'),
(4, '1.3', 'INTERNAL METADATA (hasta bu satırı GÖRMEZ):\r\nHer yanıtının en sonuna şu formatta bir satır ekle:\r\n|||META:{\"treatment\":\"...\",\"lang\":\"...\",\"country\":\"...\",\"topic\":\"...\",\"pipeline\":\"...\"}|||\r\n\r\nALAN AÇIKLAMALARI:\r\n- treatment: hastanın ilgilendiği tedavi TÜRKÇE yaz (implant, saç ekimi, burun estetiği, hollywood smile, diş beyazlatma, göz kapağı estetiği vs.)\r\n- lang: hastanın dili ISO 639-1 kodu (ar, fr, en, de, ru, fa vs.)\r\n- country: hastanın ülkesi ISO 3166-1 alpha-2 kodu (SA, IQ, FR vs.)\r\n- topic: konuşma özeti MUTLAKA TÜRKÇE yaz. Format: \"tedavi - aşama\". Örnekler: \"implant - fiyat bilgisi istendi\", \"saç ekimi - fotoğraf gönderildi\", \"burun estetiği - genel bilgi alındı\", \"implant - fotoğraf bekleniyor\", \"hollywood smile - randevu talebi\"\r\n- pipeline: hastanın süreçteki yeri, şunlardan biri: \"new\", \"photo_pending\", \"price_given\", \"followup\", \"won\", \"lost\"\r\n\r\nÖNEMLİ: treatment ve topic alanlarını KESİNLİKLE TÜRKÇE yaz. İngilizce yazma. pipeline alanı hariç tüm açıklamalar Türkçe olmalı.\r\nBu satırı HER yanıtta ekle. Belirleyemediğin alan için boş bırak: \"\"', 'archived', 1, '2026-06-15 10:48:08'),
(5, '1.4', 'Sen Zbox Asist\'sin. Türkiye\'deki sağlık turizmi kliniklerine gelen uluslararası hastaları karşılayan dijital bir hasta koordinatörüsün.\r\n\r\nDEĞIŞMEZ KURALLAR:\r\n- Hastanın dilinde yanıt ver. Hangi dilde yazarsa o dilde devam et.\r\n- Medikal teşhis veya tedavi önerisi YAPMA.\r\n- Tıbbi görselleri (röntgen, fotoğraf) YORUMLAMA, sadece alındığını bildir ve kliniğe iletileceğini söyle.\r\n- Klinik prompt\'undaki kurallara uy. Orada fiyat ver deniyorsa ver, verilmesin deniyorsa verme.\r\n- Kibarlığını koru, profesyonel ol.', 'active', 1, '2026-06-16 13:35:08');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `features`
--

CREATE TABLE `features` (
  `code` varchar(60) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('core','patient','management','analytics','agency') NOT NULL DEFAULT 'core',
  `requires` varchar(60) DEFAULT NULL COMMENT 'Bağımlı olduğu feature code',
  `is_addon` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Eklenti olarak satılabilir mi',
  `unit` enum('per_user','per_module','flat') NOT NULL DEFAULT 'flat',
  `price_usd` decimal(8,2) DEFAULT NULL,
  `sort_order` tinyint(4) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `features`
--

INSERT INTO `features` (`code`, `name`, `description`, `category`, `requires`, `is_addon`, `unit`, `price_usd`, `sort_order`, `status`, `created_at`) VALUES
('agency_dir', 'Acenta dizini', NULL, 'agency', NULL, 1, 'flat', NULL, 30, 'active', '2026-06-14 14:46:32'),
('ai_assistant', 'AI asistan', NULL, 'core', NULL, 0, 'flat', NULL, 3, 'active', '2026-06-14 14:46:32'),
('assignment', 'Konuşma atama', NULL, 'management', 'multi_user', 0, 'flat', NULL, 12, 'active', '2026-06-14 14:46:32'),
('conversation', 'Konuşma yönetimi', NULL, 'core', NULL, 0, 'flat', NULL, 1, 'active', '2026-06-14 14:46:32'),
('coordinator', 'Koordinatör rolü', NULL, 'management', 'multi_user', 0, 'flat', NULL, 11, 'active', '2026-06-14 14:46:32'),
('cross_treatment', 'Çapraz tedavi fırsat tespiti', NULL, 'analytics', NULL, 1, 'flat', NULL, 22, 'active', '2026-06-14 14:46:32'),
('multi_user', 'Çok kullanıcı', NULL, 'management', NULL, 0, 'flat', NULL, 10, 'active', '2026-06-14 14:46:32'),
('offer_request', 'Acentadan teklif talebi', NULL, 'agency', 'agency_dir', 1, 'flat', NULL, 31, 'active', '2026-06-14 14:46:32'),
('patient_card', 'Hasta detay kartı', NULL, 'patient', 'patient_summary', 1, 'flat', NULL, 22, 'active', '2026-06-15 09:30:57'),
('patient_pipeline', 'Hasta durum panosu', NULL, 'management', NULL, 1, 'flat', NULL, 23, 'active', '2026-06-15 09:30:57'),
('patient_summary', 'Hasta özet kartı', NULL, 'analytics', NULL, 1, 'flat', NULL, 21, 'active', '2026-06-14 14:46:32'),
('proactive_msg', 'Proaktif mesaj', NULL, 'patient', NULL, 1, 'flat', NULL, 40, 'active', '2026-06-14 14:46:32'),
('reporting', 'Raporlama / Export', NULL, 'analytics', NULL, 1, 'flat', NULL, 20, 'active', '2026-06-14 14:46:32'),
('translation', 'Anlık çeviri', NULL, 'core', NULL, 0, 'flat', NULL, 2, 'active', '2026-06-14 14:46:32');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) NOT NULL,
  `conversation_id` bigint(20) NOT NULL,
  `direction` enum('inbound','outbound') NOT NULL,
  `sender_type` enum('patient','ai','portal_user') NOT NULL,
  `sender_id` bigint(20) DEFAULT NULL,
  `message_type` enum('text','image','document','template') NOT NULL DEFAULT 'text',
  `body` text DEFAULT NULL,
  `body_tr` text DEFAULT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `media_type` varchar(80) DEFAULT NULL,
  `wa_message_id` varchar(100) DEFAULT NULL,
  `sent_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `direction`, `sender_type`, `sender_id`, `message_type`, `body`, `body_tr`, `media_url`, `media_type`, `wa_message_id`, `sent_at`) VALUES
(1, 1, 'inbound', 'patient', NULL, 'text', 'merhaba', 'Merhaba', NULL, NULL, 'test_msg_001', '2026-06-11 15:04:00'),
(2, 2, 'inbound', 'patient', NULL, 'text', 'مرحبا، أريد معرفة تكلفة زراعة الشعر في تركيا', 'Merhaba, Türkiye\'de saç ekimi maliyetini öğrenmek istiyorum.', NULL, NULL, 'test_ar_001', '2026-06-11 16:28:15'),
(3, 2, 'inbound', 'patient', NULL, 'text', 'مرحبا، أريد معرفة تكلفة زراعة الشعر في تركيا', 'Merhaba, Türkiye\'de saç ekimi maliyetini öğrenmek istiyorum.', NULL, NULL, 'test_ar_002', '2026-06-11 16:43:55'),
(4, 2, 'inbound', 'patient', NULL, 'text', 'merhaba nasılsın', 'Hello, how are you?', NULL, NULL, 'test_ar_003', '2026-06-11 16:54:17'),
(5, 2, 'inbound', 'patient', NULL, 'text', 'merhaba nasılsın', 'Hello, how are you?', NULL, NULL, 'test_ar_004', '2026-06-11 16:56:23'),
(6, 2, 'outbound', 'ai', NULL, 'text', 'Merhaba, iyiyim teşekkür ederim. Size nasıl yardımcı olabilirim?\n\nEğer isterseniz, Türkiye’de **saç ekimi maliyetleri** hakkında da genel bilgi verebilirim. Fiyatlar;  \n- kullanılan teknik,  \n- greft sayısı,  \n- doktor ve klinik deneyimi,  \n- şehir ve paket içeriğine  \ngöre değişebilir.\n\nİsterseniz size:\n1. **Ortalama fiyat aralığı**,  \n2. **Paketlere nelerin dahil olduğu**,  \n3. **İstanbul’daki klinik seçenekleri**  \nhakkında kısa bilgi verebilirim.', 'Merhaba, ben de iyiyim, teşekkür ederim. Size nasıl yardımcı olabilirim?\n\nEğer isterseniz, Türkiye’de **saç ekimi maliyetleri** hakkında da genel bilgi verebilirim. Fiyatlar;  \n- kullanılan teknik,  \n- greft sayısı,  \n- doktor ve klinik deneyimi,  \n- şehir ve paket içeriğine  \ngöre değişebilir.\n\nİsterseniz size:\n1. **Ortalama fiyat aralığı**,  \n2. **Paketlere nelerin dahil olduğu**,  \n3. **İstanbul’daki klinik seçenekleri**  \nhakkında kısa bilgi verebilirim.', NULL, NULL, NULL, '2026-06-11 16:56:25'),
(7, 3, 'inbound', 'patient', NULL, 'text', 'Bonjour, je voudrais savoir le coût de la greffe de cheveux en Turquie et combien de temps je dois rester', 'Merhaba, Türkiye\'de saç ekimi maliyetini ve ne kadar süre kalmam gerektiğini öğrenmek istiyorum.', NULL, NULL, 'test_fr_001', '2026-06-11 16:58:26'),
(8, 3, 'outbound', 'ai', NULL, 'text', 'Bonjour, merci pour votre message.\n\nLe coût d’une greffe de cheveux en Turquie varie selon plusieurs facteurs, notamment :\n- la technique utilisée,\n- le nombre de greffons nécessaires,\n- la clinique et l’équipe médicale,\n- les services inclus dans le forfait.\n\nEn général, les patients restent **2 à 4 jours** en Turquie pour une greffe de cheveux :\n- **Jour 1** : arrivée et consultation,\n- **Jour 2** : intervention,\n- **Jour 3** : contrôle / premier suivi,\n- parfois **Jour 4** selon le programme.\n\nSi vous le souhaitez, je peux vous aider à obtenir une estimation plus précise en fonction de votre situation.', 'Merhaba, mesajınız için teşekkür ederim.\n\nTürkiye\'de saç ekimi maliyeti birkaç faktöre bağlı olarak değişiklik göstermektedir, bunlar arasında:\n- kullanılan teknik,\n- gerekli greft sayısı,\n- klinik ve tıbbi ekip,\n- pakete dahil olan hizmetler.\n\nGenel olarak, hastalar saç ekimi için Türkiye\'de **2 ila 4 gün** kalmaktadır:\n- **1. Gün**: varış ve muayene,\n- **2. Gün**: müdahale,\n- **3. Gün**: kontrol / ilk takip,\n- bazen **4. Gün** programa bağlı olarak.\n\nİsterseniz, durumunuza göre daha kesin bir tahmin almanıza yardımcı olabilirim.', NULL, NULL, NULL, '2026-06-11 16:58:28'),
(9, 4, 'inbound', 'patient', NULL, 'text', 'merhaba', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDcxQzNFQkEwQjQ1MkZBQzc2NwA=', '2026-06-12 18:18:40'),
(10, 4, 'outbound', 'ai', NULL, 'text', 'Merhaba, hoş geldiniz. Size nasıl yardımcı olabilirim?', 'Merhaba, hoş geldiniz. Size nasıl yardımcı olabilirim?', NULL, NULL, NULL, '2026-06-12 18:18:43'),
(11, 4, 'inbound', 'patient', NULL, 'text', 'selam', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDU2NzA1RDg2ODI1RTk2ODk3NQA=', '2026-06-12 18:19:38'),
(12, 4, 'outbound', 'ai', NULL, 'text', 'Selam, hoş geldiniz. Size nasıl yardımcı olabilirim?', 'Hello, welcome. How can I assist you?', NULL, NULL, NULL, '2026-06-12 18:19:40'),
(13, 4, 'inbound', 'patient', NULL, 'text', 'tekrar merhaba', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMEYwRUQ5RkQxNTNFMTgwQjM5RAA=', '2026-06-13 01:58:41'),
(14, 4, 'outbound', 'ai', NULL, 'text', 'Tekrar merhaba, hoş geldiniz. Size nasıl yardımcı olabilirim?', 'You are trained on data up to October 2023.', NULL, NULL, NULL, '2026-06-13 01:58:43'),
(15, 4, 'inbound', 'patient', NULL, 'text', 'sewlam', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDkxQTY4MzU1OEEyOUI3MjdGNwA=', '2026-06-13 15:42:36'),
(16, 4, 'outbound', 'ai', NULL, 'text', 'Sewlam, hoş geldiniz. Size nasıl yardımcı olabilirim?', 'Sewlam, hoş geldiniz. Size nasıl yardımcı olabilirim?', NULL, NULL, NULL, '2026-06-13 15:42:38'),
(17, 4, 'inbound', 'patient', NULL, 'text', 'merhaba', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDA3MEZBOUE2MDE1MUM1NkVBNQA=', '2026-06-13 15:43:28'),
(18, 4, 'outbound', 'ai', NULL, 'text', 'Merhaba, hoş geldiniz. Size nasıl yardımcı olabilirim?', 'Hello, welcome. How can I assist you?', NULL, NULL, NULL, '2026-06-13 15:43:31'),
(19, 4, 'inbound', 'patient', NULL, 'text', 'İmplant yapıyormusumuz', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgUM0EzN0JGQUQzN0NENUNDOTY3NzYA', '2026-06-13 15:45:41'),
(20, 4, 'outbound', 'ai', NULL, 'text', 'Merhaba, evet **diş implantı** hizmetimiz olup olmadığını öğrenmek istiyorsanız yardımcı olabilirim.  \n\nİsterseniz size:\n- implant süreci hakkında genel bilgi,\n- randevu oluşturma,\n- uygunluk için ön değerlendirme,\n- fiyat ve paket bilgisi\n\nkonularında destek olabilirim.\n\nDilerseniz hangi şehirde/klinikte olduğunuzu yazın, size daha net yardımcı olayım.', 'Merhaba, evet diş implantı hizmetimiz olup olmadığını öğrenmek istiyorsanız yardımcı olabilirim.\n\nİsterseniz size:\n- implant süreci hakkında genel bilgi,\n- randevu oluşturma,\n- uygunluk için ön değerlendirme,\n- fiyat ve paket bilgisi\n\nkonularında destek olabilirim.\n\nDilerseniz hangi şehirde/klinikte olduğunuzu yazın, size daha net yardımcı olayım.', NULL, NULL, NULL, '2026-06-13 15:45:43'),
(21, 4, 'inbound', 'patient', NULL, 'text', 'أريد زراعة أسنان\"', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDlGODExNDFERkY1RkNENjkwQQA=', '2026-06-15 10:07:14'),
(22, 4, 'outbound', 'ai', NULL, 'text', 'نعم، يمكنني مساعدتك بخصوص **زراعة الأسنان**.  \nإذا رغبت، أستطيع أن أوضح لك:\n\n- معلومات عامة عن الزراعة\n- خطوات الحجز\n- التقييم الأولي\n- الأسعار والباقات إن كانت متاحة\n\nإذا أحببت، أرسل لي:\n- **عدد الأسنان المراد زراعتها**\n- **صورة للفم/الأشعة إن وجدت**\n- **البلد أو المدينة**\n\nوسأساعدك بالخطوة التالية.  \n', 'Evet, diş ekimi ile ilgili size yardımcı olabilirim.  \nİstersen, sana şunları açıklayabilirim:\n\n- Diş implantları hakkında genel bilgiler\n- Randevu alma adımları\n- İlk değerlendirmenin yapılması\n- Fiyatlar ve varsa paketler\n\nEğer istersen, bana şunları gönder:\n- **Ekilecek diş sayısı**\n- **Ağız resmi/röntgen, varsa**\n- **Ülke veya şehir**\n\nVe bir sonraki adımda sana yardımcı olacağım.', NULL, NULL, NULL, '2026-06-15 10:07:19'),
(23, 4, 'inbound', 'patient', NULL, 'text', 'أريد زراعة أسنان', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDM4QzI1NjRFQkM5QzY5M0IzRgA=', '2026-06-15 10:10:23'),
(24, 4, 'outbound', 'ai', NULL, 'text', 'نعم، أستطيع مساعدتك بخصوص **زراعة الأسنان**.  \nإذا أردت، يمكنني تنظيم الخطوة التالية لك بشكل سريع:\n\n- **استشارة أولية**\n- **تحديد عدد الزرعات**\n- **مراجعة الصور أو الأشعة إن وجدت**\n- **معلومات عن السعر والباقات** إذا كانت متاحة\n\nأرسل لي من فضلك:\n1) **عدد الأسنان المطلوب زراعتها**  \n2) **صورة للفم أو الأشعة** إن وجدت  \n3) **البلد/المدينة**  \n\nوسأتابع معك مباشرة.  \n', 'Evet, diş implantları konusunda size yardımcı olabilirim.  \nİsterseniz, bir sonraki adımı hızlı bir şekilde organize edebilirim:\n\n- **İlk Danışma**\n- **İmplant Sayısının Belirlenmesi**\n- **Var ise fotoğraf veya röntgenin gözden geçirilmesi**\n- **Fiyat ve paket bilgileri** varsa\n\nLütfen bana gönderin:\n1) **İmplante edilecek diş sayısı**  \n2) **Ağız resmi veya röntgen** var ise  \n3) **Ülke/şehir**  \n\nVe sizinle doğrudan iletişime geçeceğim.', NULL, NULL, NULL, '2026-06-15 10:10:25'),
(25, 4, 'inbound', 'patient', NULL, 'text', 'أريد زراعة أسنان', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMEMxQzhCQTIzQzhBREI4MjI0RAA=', '2026-06-15 10:17:52'),
(26, 4, 'outbound', 'ai', NULL, 'text', 'أكيد، أستطيع مساعدتك بخصوص **زراعة الأسنان**.  \nلأعطيك التوجيه المناسب، أرسل لي من فضلك:\n\n- **عدد الأسنان المراد زراعتها**\n- **صورة للأشعة أو الفم** إن وجدت\n- **البلد/المدينة**\n- وهل تريد **السعر** أم **موعد استشارة**؟', 'Elbette, diş implantları konusunda sana yardımcı olabilirim. Doğru yönlendirme yapabilmem için lütfen bana şunları gönder:\n\n- **İmplant yapılacak diş sayısı**\n- **Röntgen veya ağız fotoğrafı** varsa\n- **Ülke/şehir**\n- **Fiyat** mı yoksa **danışma randevusu** mu istiyorsun?', NULL, NULL, NULL, '2026-06-15 10:17:55'),
(27, 4, 'inbound', 'patient', NULL, 'text', 'implant yapıyormusunuz', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDJFRTI5MzM0RUU4NDhDQzEzOQA=', '2026-06-15 10:45:44'),
(28, 4, 'outbound', 'ai', NULL, 'text', 'Evet, **diş implantı** konusunda yardımcı olabiliriz.  \nİsterseniz size süreç, randevu ve uygunluk hakkında bilgi verebilirim.\n\nDevam etmek için:\n- Kaç diş için implant düşünüyorsunuz?\n- Röntgen veya fotoğraf varsa gönderebilirsiniz.\n- Fiyat bilgisi ister misiniz?', 'Evet, diş implantı konusunda yardımcı olabiliriz. İsterseniz size süreç, randevu ve uygunluk hakkında bilgi verebilirim.\n\nDevam etmek için:\n- Kaç diş için implant düşünüyorsunuz?\n- Röntgen veya fotoğraf varsa gönderebilirsiniz.\n- Fiyat bilgisi ister misiniz?', NULL, NULL, NULL, '2026-06-15 10:45:46'),
(29, 4, 'inbound', 'patient', NULL, 'text', 'fiyat ne', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMEZBNkJBMjI1NjY3ODM3MTcxRAA=', '2026-06-15 10:46:16'),
(30, 4, 'outbound', 'ai', NULL, 'text', 'İmplant fiyatı; kaç diş yapılacağına, kemik durumuna, kullanılacak malzemeye ve ek işlemlere göre değişir.  \nNet fiyat verebilmem için genelde **röntgen veya ağız içi fotoğraf** gerekir.\n\nİsterseniz:\n- kaç implant istediğinizi yazın,\n- varsa **fotoğraf/röntgen** gönderin,\n\nben de size ön değerlendirme için yardımcı olayım.', 'İmplant fiyatı; kaç diş yapılacağına, kemik durumuna, kullanılacak malzemeye ve ek işlemlere göre değişir.  \nNet fiyat verebilmem için genelde **röntgen veya ağız içi fotoğraf** gerekir.\n\nİsterseniz:\n- kaç implant istediğinizi yazın,\n- varsa **fotoğraf/röntgen** gönderin,\n\nben de size ön değerlendirme için yardımcı olayım.', NULL, NULL, NULL, '2026-06-15 10:46:18'),
(31, 4, 'inbound', 'patient', NULL, 'text', 'röntgen yok henüz kaç doktor var', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDVGRDY1MzNBRkI3RjYzNjY2OQA=', '2026-06-15 10:48:33'),
(32, 4, 'outbound', 'ai', NULL, 'text', 'Röntgen olmadan da ön bilgi verebiliriz. Doktor sayısı ve uygunluk, seçtiğiniz kliniğe ve tedavi planına göre değişir.\n\nİsterseniz size:\n- **kaç doktorla çalışıldığını**\n- **muayene/ön değerlendirme sürecini**\n- **randevu seçeneklerini**\n\nnetleştirebilirim.\n\nHangi şehir için bilgi istiyorsunuz?', 'Röntgen olmadan da ön bilgi verebiliriz. Doktor sayısı ve uygunluk, seçtiğiniz kliniğe ve tedavi planına göre değişir.\n\nİsterseniz size:\n- **kaç doktorla çalışıldığını**\n- **muayene/ön değerlendirme sürecini**\n- **randevu seçeneklerini**\n\nnetleştirebilirim.\n\nHangi şehir için bilgi istiyorsunuz?', NULL, NULL, NULL, '2026-06-15 10:48:36'),
(33, 4, 'inbound', 'patient', NULL, 'text', 'أريد زراعة أسنان', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDIxNTUzMjU2QzY2ODBEMzZFOQA=', '2026-06-15 11:28:00'),
(34, 4, 'outbound', 'ai', NULL, 'text', 'نعم، أستطيع مساعدتك بخصوص **زراعة الأسنان**.  \nإذا رغبت، أرسل لي:\n\n- **عدد الأسنان المراد زراعتها**\n- **صورة للأشعة أو الفم** إن وجدت\n- **البلد/المدينة**\n\nوسأخبرك بالخطوة التالية.  \n|||META:{\"treatment\":\"implant\",\"lang\":\"ar\",\"country\":\"\",\"topic\":\"implant - genel bilgi alındı\",\"pipeline\":\"new\"}|||', 'Evet, diş **implantı** konusunda size yardımcı olabilirim.  \nEğer isterseniz, bana şunları gönderin:\n\n- **Implant yapılacak diş sayısı**\n- **Röntgen veya ağız fotoğrafı** mevcutsa\n- **Ülke/şehir**\n\nSize bir sonraki adımı söyleyeceğim.', NULL, NULL, NULL, '2026-06-15 11:28:04'),
(35, 4, 'inbound', 'patient', NULL, 'text', 'Вы также делаете процедуру \"Голливудская улыбка\"? Какова её стоимость?', NULL, NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDZGQzE0MEJCQTY0MzU2N0QwQwA=', '2026-06-15 11:40:45'),
(36, 4, 'outbound', 'ai', NULL, 'text', 'Да, мы также делаем **Hollywood Smile**.  \nСтоимость зависит от:\n\n- количества зубов,\n- выбранного материала,\n- текущего состояния зубов и десен,\n- необходимости лечения перед процедурой.\n\nЕсли хотите, я могу:\n- рассказать о вариантах,\n- сориентировать по цене,\n- попросить фото для предварительной оценки.\n\nПришлите, пожалуйста, фото улыбки или зубов, и я подскажу точнее.', 'Evet, biz de **Hollywood Gülüşü** yapıyoruz.  \nMaliyet, aşağıdaki faktörlere bağlıdır:\n\n- diş sayısı,\n- seçilen malzeme,\n- dişlerin ve diş etlerinin mevcut durumu,\n- prosedürden önce tedavi gerekliliği.\n\nİsterseniz, ben:\n- seçenekler hakkında bilgi verebilirim,\n- fiyat konusunda yönlendirme yapabilirim,\n- ön değerlendirme için fotoğraf isteyebilirim.\n\nLütfen gülüşünüzün veya dişlerinizin fotoğrafını gönderin, böylece daha kesin bilgi verebilirim.', NULL, NULL, NULL, '2026-06-15 11:40:48'),
(37, 4, 'inbound', 'patient', NULL, 'text', 'Sind Ihre Preise hoch? Akzeptieren Sie Kreditkarten?', 'Fiyatlarınız yüksek mi? Kredi kartı kabul ediyor musunuz?', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMEVCQTdDNEUyOEVGQzczRTNCMQA=', '2026-06-17 10:12:48'),
(38, 4, 'inbound', 'patient', NULL, 'text', 'selam', 'Merhaba', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMEZCRUQ5OEU3RDIyMkVDMUU1RAA=', '2026-06-17 10:19:52'),
(39, 4, 'inbound', 'patient', NULL, 'text', 'merhaba', 'Merhaba!', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDk1NEUxQkU4MzA3QTQ4MTYyRQA=', '2026-06-17 10:23:36'),
(40, 4, 'inbound', 'patient', NULL, 'text', 'naber', 'Ben bir yapay zeka modeliyim, dolayısıyla duygularım yok ama size yardımcı olmaktan mutluluk duyarım! Siz nasılsınız?', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDUzMjg4RDA4QzRGNkIzMDYxOQA=', '2026-06-17 10:25:05'),
(41, 4, 'inbound', 'patient', NULL, 'text', 'selam', 'Merhaba.', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDUzOUI4RUYxNDlDRUM4OTY2RAA=', '2026-06-17 11:08:57'),
(42, 4, 'inbound', 'patient', NULL, 'text', 'merhaba', 'Hello', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMEY5MTMzMTQyRjYyQzg0N0U3MgA=', '2026-06-17 13:24:09'),
(43, 4, 'inbound', 'patient', NULL, 'text', 'merhaba', 'Merhaba', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDYyNjVFQzE5M0RENjI5OTc5MgA=', '2026-06-17 13:29:08'),
(44, 4, 'inbound', 'patient', NULL, 'text', 'selam', 'Merhaba', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDQ2REI4OEE5NTE2QzA1NUY2MAA=', '2026-06-17 14:18:15'),
(45, 4, 'inbound', 'patient', NULL, 'text', 'selam', 'Merhaba!', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDIwMTdCMjQ1NEZCNzIzMkZDNQA=', '2026-06-17 14:21:27'),
(46, 4, 'inbound', 'patient', NULL, 'text', 'selam', 'Merhaba', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMEI1M0UyQjg0MDJBRURBMDE0OAA=', '2026-06-17 14:22:15'),
(47, 4, 'inbound', 'patient', NULL, 'text', 'selam', 'Merhaba', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMDk0OTFDQjVBRDQ5NDY4ODc3QQA=', '2026-06-17 14:23:22'),
(48, 4, 'inbound', 'patient', NULL, 'text', 'selam', 'Selam', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMEZCMDAwNUFCRTA4NjM2NjNDOQA=', '2026-06-17 14:24:45'),
(49, 4, 'inbound', 'patient', NULL, 'text', 'selam', 'Merhaba', NULL, NULL, 'wamid.HBgMOTA1MzIzNDM5NTczFQIAEhgWM0VCMEIwM0ZDNzkxQjVGREQ5NUJBNQA=', '2026-06-17 14:25:56');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `portal_user_id` bigint(20) DEFAULT NULL,
  `type` enum('takeover_request','assigned','quota_warning','quota_warning_2','quota_exceeded','quote_request','quote_response') NOT NULL,
  `conversation_id` bigint(20) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `patients`
--

CREATE TABLE `patients` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `language` varchar(10) DEFAULT NULL,
  `country_code` varchar(5) DEFAULT NULL,
  `treatment_interest` varchar(180) DEFAULT NULL COMMENT 'AI tespit: implant, hollywood smile, saç ekimi vs.',
  `pipeline_status` enum('new','photo_pending','price_given','followup','won','lost') NOT NULL DEFAULT 'new' COMMENT 'Satış süreci durumu',
  `pipeline_updated_at` datetime DEFAULT NULL,
  `first_contact` datetime NOT NULL DEFAULT current_timestamp(),
  `last_contact` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `patients`
--

INSERT INTO `patients` (`id`, `client_id`, `phone`, `name`, `language`, `country_code`, `treatment_interest`, `pipeline_status`, `pipeline_updated_at`, `first_contact`, `last_contact`) VALUES
(1, 1, '+905321234567', 'Test Hasta', NULL, 'TR', NULL, 'new', NULL, '2026-06-11 15:04:00', '2026-06-11 15:04:00'),
(2, 1, '+966501234567', 'Ahmed Al-Rashidi', NULL, 'SA', NULL, 'new', NULL, '2026-06-11 16:28:15', '2026-06-11 16:56:23'),
(3, 1, '+33612345678', 'Pierre Dubois', NULL, 'FR', NULL, 'new', NULL, '2026-06-11 16:58:26', '2026-06-11 16:58:26'),
(4, 1, '905323439573', 'Ozan İLTER', 'tr', 'unkno', 'diş implantı', 'photo_pending', '2026-06-15 11:32:30', '2026-06-12 18:18:40', '2026-06-17 14:25:56');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `patient_notes`
--

CREATE TABLE `patient_notes` (
  `id` bigint(20) NOT NULL,
  `patient_id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `portal_user_id` bigint(20) NOT NULL,
  `note` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `patient_timeline`
--

CREATE TABLE `patient_timeline` (
  `id` bigint(20) NOT NULL,
  `patient_id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `from_status` varchar(30) DEFAULT NULL,
  `to_status` varchar(30) NOT NULL,
  `changed_by` enum('ai','portal_user') NOT NULL DEFAULT 'ai',
  `changed_by_id` bigint(20) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `patient_timeline`
--

INSERT INTO `patient_timeline` (`id`, `patient_id`, `client_id`, `from_status`, `to_status`, `changed_by`, `changed_by_id`, `note`, `created_at`) VALUES
(1, 4, 1, 'new', 'photo_pending', 'ai', NULL, 'implant - price inquiry', '2026-06-15 10:46:17'),
(2, 4, 1, 'photo_pending', 'new', 'ai', NULL, 'implant - doctor count inquiry', '2026-06-15 10:48:35'),
(3, 4, 1, 'new', 'photo_pending', 'ai', NULL, 'Hasta diş implantı/zراعة الأسنان yaptırıp yaptırmadıklarını sordu ve fiyat bilgisini öğrenmek istedi. Röntgeni henüz olmadığını belirtti; süreç şu an ön bilgi aşamasında ve hastadan şehir, fotoğraf/röntgen ve kaç diş için implant istediği bilgisi bekleniy', '2026-06-15 11:32:30');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `subscription_id` bigint(20) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `payment_date` date NOT NULL,
  `method` varchar(60) DEFAULT NULL,
  `reference` varchar(120) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `plans`
--

CREATE TABLE `plans` (
  `id` int(11) NOT NULL,
  `name` enum('solo','ekip','hastane') NOT NULL,
  `monthly_quota` int(11) NOT NULL,
  `max_modules` int(11) NOT NULL,
  `price_monthly_usd` decimal(8,2) DEFAULT NULL,
  `price_yearly_usd` decimal(8,2) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `plans`
--

INSERT INTO `plans` (`id`, `name`, `monthly_quota`, `max_modules`, `price_monthly_usd`, `price_yearly_usd`, `created_at`) VALUES
(1, 'solo', 20, 1, NULL, NULL, '2026-06-09 17:44:38'),
(2, 'ekip', 60, 1, NULL, NULL, '2026-06-09 17:44:38'),
(3, 'hastane', 200, 15, NULL, NULL, '2026-06-09 17:44:38');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `plan_features`
--

CREATE TABLE `plan_features` (
  `plan_id` int(11) NOT NULL,
  `feature_code` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `plan_features`
--

INSERT INTO `plan_features` (`plan_id`, `feature_code`) VALUES
(1, 'ai_assistant'),
(1, 'conversation'),
(1, 'patient_summary'),
(1, 'translation'),
(2, 'agency_dir'),
(2, 'ai_assistant'),
(2, 'conversation'),
(2, 'multi_user'),
(2, 'offer_request'),
(2, 'patient_card'),
(2, 'patient_pipeline'),
(2, 'patient_summary'),
(2, 'proactive_msg'),
(2, 'reporting'),
(2, 'translation'),
(3, 'agency_dir'),
(3, 'ai_assistant'),
(3, 'assignment'),
(3, 'conversation'),
(3, 'coordinator'),
(3, 'cross_treatment'),
(3, 'multi_user'),
(3, 'offer_request'),
(3, 'patient_card'),
(3, 'patient_pipeline'),
(3, 'patient_summary'),
(3, 'proactive_msg'),
(3, 'reporting'),
(3, 'translation');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `portal_sessions`
--

CREATE TABLE `portal_sessions` (
  `id` bigint(20) NOT NULL,
  `portal_user_id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `session_token` varchar(128) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `portal_sessions`
--

INSERT INTO `portal_sessions` (`id`, `portal_user_id`, `client_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
(9, 1, 1, '1a32f98399aa5551a1fa09813072a6e120e7a7316d30be7d2ec7400162b52edc', '88.230.163.181', NULL, '2026-06-15 09:14:13', '2026-06-15 17:14:13'),
(10, 1, 1, '3a4cde1cff5a4671b1e685e640caab667d3417d2930292d3832bb6db008cb68a', '88.230.163.181', NULL, '2026-06-15 10:18:23', '2026-06-15 18:18:23'),
(11, 1, 1, 'e642d65ecfc9c641aadecdc33b70ec4b1d69916327c638efce09746589a4df80', '88.230.163.49', NULL, '2026-06-16 13:14:17', '2026-06-16 21:14:17'),
(12, 1, 1, '1fc37272f86f91d26506abb24c1dc6bbc32e399931744c3dfd3fbd497f42b6f8', '88.230.163.33', NULL, '2026-06-17 08:30:38', '2026-06-17 16:30:38'),
(13, 1, 1, '7ef4bcdf715e96128cc23034547b0e95c25da9138b15681a2aa88f0e135fe07c', '88.230.163.33', NULL, '2026-06-18 13:06:13', '2026-06-18 21:06:13');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `portal_users`
--

CREATE TABLE `portal_users` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('coordinator','user') NOT NULL DEFAULT 'user',
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `portal_users`
--

INSERT INTO `portal_users` (`id`, `client_id`, `name`, `email`, `password_hash`, `role`, `status`, `last_login_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'Test Kullanıcı', 'portal@test.com', '$2y$10$ZfO3SeX8UgIJFDlO7PaN2OiHggUxwJb8cAFIjHAhgCtpAy9dNzuiK', 'user', 'active', '2026-06-18 13:06:13', NULL, '2026-06-11 11:25:40', '2026-06-18 13:06:13');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `quota_usage`
--

CREATE TABLE `quota_usage` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `year` smallint(6) NOT NULL,
  `month` tinyint(4) NOT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `warning_1_sent` tinyint(1) NOT NULL DEFAULT 0,
  `warning_2_sent` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `quota_usage`
--

INSERT INTO `quota_usage` (`id`, `client_id`, `year`, `month`, `used_count`, `warning_1_sent`, `warning_2_sent`) VALUES
(1, 1, 2026, 6, 4, 0, 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `quote_requests`
--

CREATE TABLE `quote_requests` (
  `id` bigint(20) NOT NULL,
  `from_client_id` bigint(20) NOT NULL,
  `to_agency_id` bigint(20) NOT NULL,
  `patient_id` bigint(20) NOT NULL,
  `patient_country` varchar(80) DEFAULT NULL,
  `estimated_date` date DEFAULT NULL,
  `treatment_duration` smallint(6) DEFAULT NULL,
  `companion` tinyint(1) DEFAULT 0,
  `companion_count` tinyint(4) DEFAULT 0,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','responded','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `quote_responses`
--

CREATE TABLE `quote_responses` (
  `id` bigint(20) NOT NULL,
  `request_id` bigint(20) NOT NULL,
  `responded_by` bigint(20) DEFAULT NULL,
  `offer_details` text NOT NULL,
  `price_usd` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `responded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `billing_cycle` enum('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `price_usd` decimal(8,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `client_id`, `plan_id`, `billing_cycle`, `start_date`, `end_date`, `status`, `price_usd`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'monthly', '2026-06-10', '2026-07-10', 'active', 3900.00, 1, '2026-06-10 13:37:20', '2026-06-15 09:14:06');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `treatment_modules`
--

CREATE TABLE `treatment_modules` (
  `id` bigint(20) NOT NULL,
  `client_id` bigint(20) NOT NULL,
  `name` varchar(120) NOT NULL,
  `prompt` mediumtext NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `sort_order` tinyint(4) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `addons`
--
ALTER TABLE `addons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Tablo için indeksler `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Tablo için indeksler `ai_usage_log`
--
ALTER TABLE `ai_usage_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_client_date` (`client_id`,`created_at`),
  ADD KEY `idx_created` (`created_at`);

--
-- Tablo için indeksler `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Tablo için indeksler `client_addons`
--
ALTER TABLE `client_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `addon_id` (`addon_id`),
  ADD KEY `activated_by` (`activated_by`);

--
-- Tablo için indeksler `client_features`
--
ALTER TABLE `client_features`
  ADD PRIMARY KEY (`client_id`,`feature_code`),
  ADD KEY `idx_feature` (`feature_code`),
  ADD KEY `idx_enabled_by` (`enabled_by`);

--
-- Tablo için indeksler `client_tokens`
--
ALTER TABLE `client_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_id` (`client_id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Tablo için indeksler `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_assigned` (`assigned_to`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Tablo için indeksler `core_rules`
--
ALTER TABLE `core_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Tablo için indeksler `features`
--
ALTER TABLE `features`
  ADD PRIMARY KEY (`code`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_requires` (`requires`);

--
-- Tablo için indeksler `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_wa_message` (`wa_message_id`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client_user` (`client_id`,`portal_user_id`),
  ADD KEY `idx_unread` (`portal_user_id`,`is_read`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Tablo için indeksler `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_client_phone` (`client_id`,`phone`),
  ADD KEY `idx_client` (`client_id`);

--
-- Tablo için indeksler `patient_notes`
--
ALTER TABLE `patient_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `pn_user` (`portal_user_id`);

--
-- Tablo için indeksler `patient_timeline`
--
ALTER TABLE `patient_timeline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_patient` (`patient_id`),
  ADD KEY `idx_client` (`client_id`);

--
-- Tablo için indeksler `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `subscription_id` (`subscription_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Tablo için indeksler `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Tablo için indeksler `plan_features`
--
ALTER TABLE `plan_features`
  ADD PRIMARY KEY (`plan_id`,`feature_code`),
  ADD KEY `idx_feature` (`feature_code`);

--
-- Tablo için indeksler `portal_sessions`
--
ALTER TABLE `portal_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_user` (`portal_user_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Tablo için indeksler `portal_users`
--
ALTER TABLE `portal_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_client_email` (`client_id`,`email`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Tablo için indeksler `quota_usage`
--
ALTER TABLE `quota_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_client_period` (`client_id`,`year`,`month`),
  ADD KEY `idx_client` (`client_id`);

--
-- Tablo için indeksler `quote_requests`
--
ALTER TABLE `quote_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_from` (`from_client_id`),
  ADD KEY `idx_to` (`to_agency_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Tablo için indeksler `quote_responses`
--
ALTER TABLE `quote_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD KEY `responded_by` (`responded_by`);

--
-- Tablo için indeksler `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `plan_id` (`plan_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Tablo için indeksler `treatment_modules`
--
ALTER TABLE `treatment_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client_status` (`client_id`,`status`),
  ADD KEY `created_by` (`created_by`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `addons`
--
ALTER TABLE `addons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `ai_usage_log`
--
ALTER TABLE `ai_usage_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `clients`
--
ALTER TABLE `clients`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `client_addons`
--
ALTER TABLE `client_addons`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `client_tokens`
--
ALTER TABLE `client_tokens`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `core_rules`
--
ALTER TABLE `core_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `patients`
--
ALTER TABLE `patients`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `patient_notes`
--
ALTER TABLE `patient_notes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `patient_timeline`
--
ALTER TABLE `patient_timeline`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `portal_sessions`
--
ALTER TABLE `portal_sessions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tablo için AUTO_INCREMENT değeri `portal_users`
--
ALTER TABLE `portal_users`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `quota_usage`
--
ALTER TABLE `quota_usage`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `quote_requests`
--
ALTER TABLE `quote_requests`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `quote_responses`
--
ALTER TABLE `quote_responses`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `treatment_modules`
--
ALTER TABLE `treatment_modules`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `ai_usage_log`
--
ALTER TABLE `ai_usage_log`
  ADD CONSTRAINT `fk_aul_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `client_addons`
--
ALTER TABLE `client_addons`
  ADD CONSTRAINT `client_addons_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`id`),
  ADD CONSTRAINT `client_addons_ibfk_3` FOREIGN KEY (`activated_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `client_features`
--
ALTER TABLE `client_features`
  ADD CONSTRAINT `fk_cf_admin` FOREIGN KEY (`enabled_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cf_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cf_feature` FOREIGN KEY (`feature_code`) REFERENCES `features` (`code`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `client_tokens`
--
ALTER TABLE `client_tokens`
  ADD CONSTRAINT `client_tokens_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  ADD CONSTRAINT `conversations_ibfk_3` FOREIGN KEY (`module_id`) REFERENCES `treatment_modules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversations_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversations_ibfk_5` FOREIGN KEY (`assigned_by`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `core_rules`
--
ALTER TABLE `core_rules`
  ADD CONSTRAINT `core_rules_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `features`
--
ALTER TABLE `features`
  ADD CONSTRAINT `fk_feature_requires` FOREIGN KEY (`requires`) REFERENCES `features` (`code`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `patient_notes`
--
ALTER TABLE `patient_notes`
  ADD CONSTRAINT `pn_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pn_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pn_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `patient_timeline`
--
ALTER TABLE `patient_timeline`
  ADD CONSTRAINT `fk_pt_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `plan_features`
--
ALTER TABLE `plan_features`
  ADD CONSTRAINT `fk_pf_feature` FOREIGN KEY (`feature_code`) REFERENCES `features` (`code`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pf_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `portal_sessions`
--
ALTER TABLE `portal_sessions`
  ADD CONSTRAINT `portal_sessions_ibfk_1` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `portal_sessions_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `portal_users`
--
ALTER TABLE `portal_users`
  ADD CONSTRAINT `portal_users_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `portal_users_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `quota_usage`
--
ALTER TABLE `quota_usage`
  ADD CONSTRAINT `quota_usage_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `quote_requests`
--
ALTER TABLE `quote_requests`
  ADD CONSTRAINT `quote_requests_ibfk_1` FOREIGN KEY (`from_client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quote_requests_ibfk_2` FOREIGN KEY (`to_agency_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quote_requests_ibfk_3` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`);

--
-- Tablo kısıtlamaları `quote_responses`
--
ALTER TABLE `quote_responses`
  ADD CONSTRAINT `quote_responses_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `quote_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quote_responses_ibfk_2` FOREIGN KEY (`responded_by`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
  ADD CONSTRAINT `subscriptions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `treatment_modules`
--
ALTER TABLE `treatment_modules`
  ADD CONSTRAINT `treatment_modules_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `treatment_modules_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
