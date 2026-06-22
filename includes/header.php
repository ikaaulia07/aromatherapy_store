<?php
require_once __DIR__ . '/functions.php';
$appUrl = getAppUrl();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' | Aromatherapy Store' : 'Aromatherapy Store - Lilin Aromaterapi & Essential Oil Premium'; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Toko lilin aromaterapi, essential oil, diffuser, dan reed diffuser premium dengan bahan alami dan menenangkan untuk kesegaran rumah Anda.">
    <meta name="keywords" content="lilin aromaterapi, aromatherapy candle, essential oil, diffuser, reed diffuser, aromatherapy store">
    
    <!-- Google Fonts: Playfair Display (Serif) & Inter (Sans-Serif) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Global CSS Styles -->
    <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/style.css">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?= $appUrl ?>/assets/css/<?= $extraCss ?>">
    <?php endif; ?>
    <?php if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false): ?>
        <script src="<?= $appUrl ?>/assets/js/admin.js" defer></script>
    <?php endif; ?>
</head>
<body>
