<?php
namespace App\Enums;
use App\Traits\EnumsToArray;

enum OrderPaidReportStatus : string
{
    use EnumsToArray;

    case NONE = 'NONE'; // default
    case SENT = 'SENT'; // đã gửi thông báo `đã thanh toán`
    // case PENDING = 'PENDING'; // đang chờ admin xác nhận
    case COMPLETED = 'COMPLETED'; // đã thanh toán
}