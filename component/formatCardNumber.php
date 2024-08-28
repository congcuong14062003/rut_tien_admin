<?php
function formatCardNumber($cardNumber)
{
    // Remove any spaces or dashes from the card number
    $cleaned = preg_replace('/[^0-9]/', '', $cardNumber);

    // Format the card number into xxxx-xxxx-xxxx-xxxx
    return implode('-', str_split($cleaned, 4));
}
?>