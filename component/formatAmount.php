<?php 
function formatAmount($amount) {
  if ($amount == 0) {
      return '0 VND';
  } elseif ($amount) {
      return number_format($amount, 0, ',', '.') . ' VND';
  }
  return '0 VND'; // Trường hợp amount là null hoặc không có giá trị
}
?>