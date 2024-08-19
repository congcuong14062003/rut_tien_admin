<?php
     function formatCardNumber($cardNumber) {
        $firstFour = substr($cardNumber, 0, 4);
        $lastFour = substr($cardNumber, -4);
        $hiddenPart = str_repeat('*', strlen($cardNumber) - 8);
        return $firstFour . $hiddenPart . $lastFour;
    }
?>