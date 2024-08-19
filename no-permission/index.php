<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .container {
            text-align: center;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 20px;
        }
        .container h1 {
            color: #e74c3c;
            font-size: 2.5rem;
        }
        .container p {
            color: #333;
            font-size: 1.1rem;
            margin: 10px 0;
        }
        .container a {
            display: inline-block;
            margin: 10px;
            padding: 10px 20px;
            color: #fff;
            background-color: #3498db;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1rem;
        }
        .container a:hover {
            background-color: #2980b9;
        }
        .container .fa-exclamation-triangle {
            font-size: 4rem;
            color: #e74c3c;
        }
    </style>
    <title>Không Có Quyền</title>
</head>

<body>
    <div class="container">
        <i class="fas fa-exclamation-triangle"></i>
        <h1>Không Có Quyền</h1>
        <p>Rất tiếc, bạn không có quyền truy cập vào trang này.</p>
        <a href="/index.php">Về Trang Chính</a>
    </div>
</body>

</html>
