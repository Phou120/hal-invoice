<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Form</title>
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.9.3/css/bulma.min.css"> --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.0/css/bulma.min.css">
  </head>
  <style>
    .invoice {
      padding: 12px 12px 54px 12px;
      font-size: 16px;
      color: black;
      margin-bottom: 12px;
      font-family: 'BoonBaan' !important;
    }
    .invoice-border-top{
        margin-top: 10px;
        border-top: 20px solid #222;
    }
    .desc ol ul li, .desc ol li {
      list-style-type: decimal;
      list-style-position: inside;
      -webkit-margin-before: 1em;
      -webkit-margin-after: 1em;
      -webkit-margin-start: 0px;
      -webkit-margin-end: 0px;
      -webkit-padding-start: 0px;
      margin: 0px;
    }
    .table td, .table th {
      border: 1px dashed #dbdbdb;
      border-width: 0 0 1px;
      padding: 12px 6px;
      vertical-align: top;
    }
    .invoice-content {
      padding: 12px 22px;
    }
    .invoice>.table>.h-table td, .table th {
      border:none;
      border-width: 0 0 1px;
      padding: 1em .75em;
      vertical-align: top;
    }
    .label-title {
      font-size: 14px;
      font-weight: 500;
      color: #445;
    }
    .list-total{
      font-size: 16px;
      font-weight: 600;
    }
    .list_tatal .sub{
      padding-left: 20px;
      color: #444;
    }
    .sub{
      padding-left: 8px;
      font-weight: 500;
    }
    .list-total li{
      display: flex;
      justify-content: space-between;
      text-align: left;
    }
    .invoice-footer{
      display: flex;
    }
    .card-footer {
      padding: 12px 22px;
    }
    .invoice-header {
      padding: 12px 22px;
    }
    .border {
      border-bottom: 2px solid #777;
    }
    .border-total {
      border-bottom: 4px solid #777;
      margin: 1rem 0;
    }
    .title {
      font-size: 18px;
      color: #000;
      margin-bottom: 0px !important;
    }
    .sub-title{
      font-size: 16px;
      color: #000;
    }
    .header {
      width: 100%;
    }
    .bill-from {
      text-align: left;
    }
    .label {
      font-weight: 600;
      color: #333;
    }
    .table {
      width: 100%;
      padding: 20px;
    }
    .card-header-icon {
      padding: 4px 8px;
    }
    .card-header-icon> .logo {
      height: 112px;
      text-align: center;
    }
    .card-footer {
      border-top: black;
    }
    .card-footer-item {
      justify-content: flex-end;
      align-items: flex-end;
      border-top: #000;
    }
    .card-footer-item>ul>li {
      font-size: 18px;
      font-weight: bold;
    }
    .invoice>.table td,
    .invoice>.table>th>tbody>tr {
      border-width: 0 0 1px;
      padding: 200px 15px;
      vertical-align: baseline;
      border: none;
      border-bottom: 1px dashed rgb(191, 191, 191);
    }

    .invoice> .table> tbody>tr:last-of-type {
      border-bottom: 1px solid #bfbfbf;
    }
    .table>tbody>tr{
      border-bottom: 1px dashed #000;
    }
    .table>tbody>tr:last-of-type{
      border-bottom: 2px solid #777;
    }
    .desc{
      font-size: 15px;
      color: #363636;
      letter-spacing: 0.4px;
      line-height: 22px;
      font-weight: 500;
      padding-left: 8px;
    }
    .total-m{
      font-size: 20px;
      font-weight: 700;
      color: #333;
    }
    .desc-title{
      padding-bottom: 2px;
      font-size: 16px;
      font-weight: 700;
    }
    .invoice .small-border{
      border-bottom: 0.5px solid #a8a2a2fb;
      margin-top: 150px;
    }
    .invoice .signature{
      margin-left: 20px;
      font-style: italic;
      letter-spacing: 1px;
    }
    .invoice .sing{
      margin-top: 100px;
      margin-left: 100px;
      border-bottom: 1px solid #c9c6c6;
      width: 20%;
    }
    .invoice .note{
      white-space: break-spaces;
    }
  </style>
  <body>
    <div class="container">
      <div>
        <div class="invoice-border-top"></div>
        <div class="invoice">
          <div class="invoice-header">
            <div class="columns">
                {{-- ຂໍ້ມູນບໍລິສັດເຮົາ --}}
                <div class="column is-8">
                    <div class="header columns">
                        <div class="logo column is-4">
                        <img src="https://admin.haltech.la/generated/admin/img/logo.74a3eec9.png" alt="logo" width="152">
                        </div>
                        <div class="bill-from column is-8">
                        {{-- <h1 class="title">HOUNG AH LOUN TECHNOLOGY CO.,LTD</h1> --}}
                        <h1 class="title">{{ $data['company_name'] }}</h1>
                        <p><strong>Business Number:</strong> 020 99999564</p>
                        <p class="label">info@haltech.la</p>
                        <p class="label">Don koi Village, Sisattanak District, Vientiane Capital, Laos</p>
                        </div>
                    </div>
                </div>
                {{-- end Company --}}

                {{-- ຂໍ້ມູນ Quotation --}}
                <div class="column is-4">
                    <div class="field is-horizontal">
                        <div class="field-label is-normal">
                            <p class="sub-title">ໃບສະເໜີລາຄາ ລະບົບຂົນສົ່ງດ່ວນ</p>
                            <p class="label">IV00B883EV9</p>
                            <p class="sub-title">Created Date</p>
                            <p class="label">2023-08-20</p>
                            <p class="sub-title">DUE DATE</p>
                            <p class="label">2023-12-20</p>
                            <p class="sub-title">BALANCE DUE</p>
                            <p>$ 50,000</p>
                        </div>
                    </div>
                </div>
                {{-- End Quotation--}}
            </div>
            <hr class="border">

            {{-- ຂໍ້ມູນ ລູກຄ້າ --}}
            <div class="columns">
              <div class="column">
                  <div class="field is-horizontal">
                      <label class="label-title">QUOTATION TO</label>
                  </div>
                  <div>
                      <label class="title">HAL Logistics</label>
                      <p><strong>Business Number </strong> 020 99999938</p>
                      <p>hallogistics@gmail.com</p>
                      <p>Hongkha Rd, Vientiane Capital</p>
                  </div>
              </div>
            </div>
            {{-- End Customer --}}
          </div>
          <div class="invoice-body">
            <div class="invoice-content">
              <div class="card-layout">
                <div class="bill">

                {{-- Quotation Detail --}}
                  <table class="table">
                    <thead>
                        <tr style="border-bottom: 2px solid #222;border-top:2px solid #222;background-color:#eee">
                            <th style="width:50%;">DESCRIPTION</th>
                            <th>RATE</th>
                            <th>QTY</th>
                            <th>AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- @foreach($data['quotation_details'] as $quotation_detail) --}}
                        <tr class="h-table">
                          <td class="width:50%;">
                            <div class="desc-title">
                                {{-- {{$quotation_detail['name']}} --}}
                              ໃບສະເໜີລາຄາພັດທະນາເສີມລະບົບຕີກັບ
                            </div>
                          <div class="desc">
                              {{-- {!!$quotation_detail->description!!} --}}
                              1 . ເພີ່ມແຈ້ງເຕືອນຫາລູກຄ້າເວລາກົດຕີກັບ ຂໍ້ຄວາມ "ພັດສະດຸຖືກຕີກັບ
                              ສາຂາຕົ້ນທາງແລ້ວ - ສາຂາ........" (ໃຫ້ແຈ້ງທັງຜູ້ຝາກ ແລະ ຜູ້ຮັບ)
                              2 . ປ່ຽນຂໍ້ຄວາມແຈ້ງເຕືອນເວລາເຄື່ອງຮອດປາຍທາງ
                              - ເງື່ອນໄຂຂອງວັນທີບວກໄປ 10 ມື້ຫຼັງຈາກຮອດປາຍທາງ
                              ຕົວຢ່າງ: ເຄື່ອງຮອດວັນທີ1/7 ຂໍ້ຄວາມ "ພັດສະດຸຮອດປາຍທາງແລ້ວ
                              ທ່ານສາມາດເຂົ້າຮັບພັດສະດຸໄດ້ທີ່ສາຂາປາຍທາງ ກ່ອນວັນທີ
                              11/07/2023 ຫາກເກີນກໍານົດພັດສະດຸຈະຖືກຕີກັບສາຂາຕົ້ນທາງ"
                              3 . ປ່ຽນຂໍ້ຄວາມແຈ້ງເຕືອນລູກຄ້າກໍລະນີຕີກັບຮອດຕົ້ນທາງແລ້ວ
                              - ເງື່ອນໄຂຂອງວັນທີບວກໄປ 30 ມື້ຫຼັງຈາກຕີກັບຮອດຕົ້ນທາງ
                              ຕົວຢ່າງ: "ພັດສະດຸຕີກັບຮອດສາຂາ..........ແລ້ວ ທ່ານສາມາດເຂົ້າຮັບ
                              ພັດສະດຸໄດ້ທີ່ສາຂາ.......... ກ່ອນວັນທີ30/07/2023 ຫາກເກີນກໍານົດຖືວ່າ
                              ລູກຄ້າສະລະສິດໃນການຮັບເຄື່ອງ"
                              4 . ເພີ່ມ ບິນຕີກັບດໍາເນີນການ ກັບ ຕີກັບສໍາເລັດ ພາກສ່ວນຂອງບິນຕີກັບ
                              ໄວ້ຕ່າງຫາກໃນແອັບລູກຄ້າ
                              5 . ເພີ່ມ tab ບິນຕີກັບດໍາເນີນການ ກັບ ຕີກັບສໍາເລັດ ໄວ້ຕ່າງຫາກໃນແອັບ
                              ລູກຄ້າ
                              6 . ເຄື່ອງຕີກັບຮອດສາຂາປາຍທາງໃຫມ່ໃຫ້ປ່ຽນຂໍ້ຄວາມ tracking ໃຫມ່
                              ເປັນ ສໍາລັບ ເຄື່ອງຄ້າງສາງເກີນ 30 ວັນຈະຫມົດສິດຮັບ
                              7 . ເຄື່ອງຕີກັບໃຫ້ສ້າງ tracking ໃຫມ່ສໍາລັບການຕີກັບ
                          </div>
                          </td>
                          <td>$ 250</td>
                          <td>2</td>
                          <td>$ 500</td>
                        </tr>
                        {{-- @endforeach --}}
                    </tbody>
                  </table>
                {{-- End Quotation Detail --}}

                  <div class="columns">
                    <div class="column">
                        <div class="signature">
                            <p>Signature</p>
                        </div>
                        {{-- <div class="sing"></div> --}}
                    </div>
                    <div class="column is-4">
                        <ul style="list-style:none;">
                            <div class="list-total">
                            <li>
                            SUB-TOTAL
                                <span class="sub">$ 500</span>
                            </li>
                            <li>
                                TAX (7%)
                                <span class="sub">$ 35</span>
                            </li>
                            <li>
                                DISCOUNT (5%)
                                <span class="sub">$ 25</span>
                            </li>
                            <hr class="border-total">
                            <li class="total-m">
                                TOTAL<span>$ 510</span>
                            </li>
                            </div>
                        </ul>
                    </div>
                  </div>
                  {{-- @if(isset($invoice->note)) --}}
                    <hr class="small-border">
                    <div class="invoice-footer">
                        <p class="note">
                          ຖ້າເຮັດສຳເລັດເເລ້ວເຮົາຈະອະທິບາຍຕື່ມເດີ.
                        </p>
                    </div>
                  {{-- @endif --}}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
