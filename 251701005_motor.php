<?php
declare(strict_types=1);


function adim(array $s, string $a): array {
    if ($s['bitti']) {
        return $s;
    }

   
    if (preg_match('/^T-([a-z]+)-(\d+)$/', $a, $eslesme)) {
        $urun = $eslesme[1];
        $istenen = (int) $eslesme[2];
        if (!isset($s['urunler'][$urun]) || $istenen < 1) {
            return $s;
        }
        $birimMaliyet = $s['urunler'][$urun]['fiyat'] * 2;
        if ($istenen >= 5) {
            $birimMaliyet = (int) round($birimMaliyet * 0.80);
        }
        $alinabilecek = intdiv(max(0, $s['kasa']), $birimMaliyet);
        $alinan = min($istenen, $alinabilecek);
        $s['kasa'] -= $alinan * $birimMaliyet;
        $s['urunler'][$urun]['stok'] += $alinan;
        $ad = $s['urunler'][$urun]['ad'];
        $s['log'][] = "🚚 {$ad} tedarik: {$alinan} adet";
        return $s;
    }

    
    if ($a === 'M') {
        
        if ($s['gunluk_musteri'] >= 5) {
            return $s;
        }
        $s['gunluk_musteri']++;
        $s['musteri_sira']++;
        $m = musteri($s['no'], $s['musteri_sira']);
        $urun = $m['urun'];
        $ad = $s['urunler'][$urun]['ad'];
        $fiyat = $s['urunler'][$urun]['fiyat'];

        
        if ($m['tip'] === 'Öğrenci' || $m['tip'] === 'Burslu Öğrenci') {
            $fiyat = (int) round($fiyat * 0.95);
        }

       
        $alinan = 0;
        if ($s['elektrik'] > 0) {
            $s['elektrik']--;
            $s['memnuniyet'] -= 8;
            $s['seri'] = 0;
            $s['gunluk_ret']++;
        } elseif ($s['urunler'][$urun]['stok'] > 0 && $m['butce'] >= $fiyat) {
            $alinan = 1;
            $s['urunler'][$urun]['stok']--;
            $s['kasa'] += $fiyat;
            $s['memnuniyet'] += 2;
            $s['seri']++;
            $s['ret_sayac'][$urun] = 0;
        } else {
            $s['memnuniyet'] -= 4;
            $s['seri'] = 0;
            $s['gunluk_ret']++;
            
            if ($s['urunler'][$urun]['stok'] > 0 && $m['butce'] < $fiyat) {
                $s['ret_sayac'][$urun]++;
                if ($s['ret_sayac'][$urun] >= 2) {
                    $s['urunler'][$urun]['fiyat'] = (int) round(
                        $s['urunler'][$urun]['fiyat'] * 0.90
                    );
                    $s['ret_sayac'][$urun] = 0;
                }
            }
        }

        $s['memnuniyet'] = max(0, min(100, $s['memnuniyet']));
        $sonuc = $alinan > 0 ? "Satış: {$alinan} adet" : 'Satış yok';
        $s['log'][] = "👤 {$m['ad']} ({$m['tip']}, bütçe {$m['butce']} TL) {$ad} istiyor → {$sonuc}";

        
        $olay = olay($s['no'], $s['musteri_sira']);
        if ($olay === 'elektrik') {
            $s['elektrik'] = 1;
            $s['log'][] = '⚡ Elektrik kesintisi';
        } elseif ($olay === 'bahsis') {
            
            $s['kasa'] += $s['memnuniyet'] >= 80 ? 20 : 5;
            $s['log'][] = '😊 Memnun müşteri';
        }
       
        return $s;
    }

    
    if ($a === 'G') {
        if ($s['gunluk_musteri'] < 5 || $s['gun'] >= 5) {
            return $s;
        }

        $gun = $s['gun'];
        $s['kasa'] -= 50 + 10 * $gun;

       
        if ($s['gunluk_ret'] >= 2) {
            $s['memnuniyet'] = max(0, $s['memnuniyet'] - 5);
        }


        foreach (['tost', 'hamburger'] as $urun) {
            $s['urunler'][$urun]['stok'] = (int) ceil(
                $s['urunler'][$urun]['stok'] / 2
            );
        }

        $s['log'][] = "🌙 Gün {$gun} kapandı";
        $s['gun']++;
        $s['gunluk_musteri'] = 0;
        $s['gunluk_ret'] = 0;
        $s['seri'] = 0;
        $s['elektrik'] = 0;
        $s['yogunluk'] = false;
    }

    return $s;
}
