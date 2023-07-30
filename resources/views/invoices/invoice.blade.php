<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice Form</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.9.3/css/bulma.min.css">
</head>
<style>
    body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  /* background-color: #f1f1f1; */
}

.container {
  max-width: 500px;
  margin: 50px auto;
  background-color: #fff;
  padding: 20px;
  border-radius: 5px;
  /* box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); */
}

h1 {
  text-align: center;
  margin-bottom: 30px;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 5px;
}

input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 3px;
  font-size: 16px;
}

button {
  display: block;
  width: 100%;
  padding: 10px;
  background-color: #4CAF50;
  color: #fff;
  border: none;
  border-radius: 3px;
  font-size: 16px;
  cursor: pointer;
}

button:hover {
  background-color: #45a049;
}

button:active {
  background-color: #3e8e41;
}
table {
  width: 100% !important; /* override the default Bulma styles */
}
.total{
    text-align: right;
}
</style>
<body>
  <div class="container">
    <di>
        <p style="text-align: center; font-family: 'Phetsarath OT'">
            ສາທາລະນະລັດ ປະຊາທິປະໄຕ ປະຊາຊົນລາວ <br>
            ສັນຕິພາບ ເອກະລາດ ປະຊາທິໄຕ ເອກະພາບ ວັດທະນາຖາວອນ
        </p>
        <br>
       <h1 style="font-family: 'Phetsarath OT'; font-size: 20px">{{ $invoice->invoice->invoice_name }}</h1>

        <table class="table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
                @foreach($invoice->details as $key => $detail)
                    <tr>
                        <td>{{ $detail->name }}</td>
                        <td>{{ $detail->amount }}</td>
                        <td>{{ $detail->price }}</td>
                        <td>{{ $detail->total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <hr>
        <p class="total">Total: 200,000 Kip</p>
        <p style="text-align: right">Tax: 200,000 Kip</p>
        <p style="text-align: right">Discount: 200,000 Kip</p> 
    </di>
  </div>
</body>
</html>
