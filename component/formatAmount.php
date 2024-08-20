<?php 
function formatAmount($amount) {
  if ($amount == 0) {
      return '0 $';
  } elseif ($amount) {
      return number_format($amount, 0, ',', '.') . ' $';
  }
  return '0 $'; // Trường hợp amount là null hoặc không có giá trị
}
?>