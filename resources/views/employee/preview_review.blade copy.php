@extends('main')

@section('content')
<div class="main-pdf" style="padding:40px">
    <img src="avatar/thumb-HORUS.png" alt="Logo" style="height: 40px"><br>
    <span style="font-size: 12px; font-weight: bold; margin-top: -100px">CÔNG TY TNHH HORUS PRODUCTIONS</span>
    <div style="margin-left: 360px; margin-top: -60px;">
        @if($review->period == 2 || $review->period == 3)
            <span style="font-weight: bold">VĂN BẢN ĐÁNH GIÁ QUÁ TRÌNH <br><p style="margin-left: 10%">THỰC HIỆN CÔNG VIỆC<p></span>
        @elseif($review->period == 4)
            <span style="font-weight: bold">VĂN BẢN ĐÁNH GIÁ QUÁ TRÌNH <br><p style="margin-left: 30%">HỌC VIỆC<p></span>
        @else
            <span style="font-weight: bold">VĂN BẢN ĐÁNH GIÁ QUÁ TRÌNH <br><p style="margin-left: 30%">THỬ VIỆC<p></span>
        @endif  
    </div>
    <br>
    <div style="padding-top: 10px">
        <span style="margin-right: 180px">Nhân viên: {{ $employee->fullname }}</span>
        <span>Ngày đánh giá: {{ $review->start_date }}</span>
    </div>
    
    <div style="padding-top: 20px; font-weight: bold; margin-bottom:10px">
        <span> * Thống kê từ {{$before_review}} đến {{ $review->start_date }}</span>
    </div>
    <div class="table-wrapper" style=" margin-bottom:10px">
        <table class="timesheet-table">
            <thead>
            <tr>
                <th style="font-size:12px;width:100px">Đi muộn</th>
                <th style="font-size:12px;width:100px">Về sớm</th>
                <th style="font-size:12px;width:100px">Công thực tế</th>
                <th style="font-size:12px;width:100px">Nghỉ phép có lương</th>
                <th style="font-size:12px;width:100px">Nghỉ phép không lương</th>
                <th style="font-size:12px">Tỉ lệ đi muộn</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{$user_table->users['0']->late_count}}</td>
                <td>{{$user_table->users['0']->early_count}}</td>
                <td>{{number_format($user_table->users['0']->origin_workday,2)}}</td>
                <td>{{$user_table->users['0']->paid_leave}}</td>
                <td>{{$user_table->users['0']->un_paid_leave}}</td>
                <td>{{ number_format(($user_table->users[0]->late_count / $user_table->users[0]->origin_workday) * 100, 2) }}%</td>

            </tr>
            </tbody>
        </table>
    </div>
    @if ($violations && count($violations)>0)
    <div style="">
        @foreach ($violations as $key => $value)
            <span style="color: red; font-size:13px">Vi phạm lần {{$key+1}}: {{ $value->description }} ({{$value->time}})</span>
            <br>
        @endforeach
    </div>
    @endif
    <div style="padding-top: 20px; font-weight: bold">
        <span>1. Đánh giá nội dung theo thang điểm</span>
    </div>
    <div style="padding-top: 20px">
        <!-- prepare the table data in the controller or model layer -->
        @php
            $positionKey = [];

            foreach ($employees as $item) {
                if ($item['position'] == 0) {
                    $positionKey['member'] = $item['position'];
                } elseif ($item['position'] == 0.5) {
                    $positionKey['mentor'] = $item['position'];
                } elseif ($item['position'] == 1) {
                    $positionKey['leader'] = $item['position'];
                } elseif ($item['position'] == 2) {
                    $positionKey['pm'] = $item['position'];
                }
            }
        @endphp
        <!-- render the table in the view -->
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Nội dung đánh giá</th>
                    @foreach ($employees as $item)
                        <th>{{ $item['fullname'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($content as $key => $value)
                    <tr>
                        <td>{{ $key+1 }}</td>
                        <td>{{ $value->content }}</td>
                        @if(isset($positionKey['member']))<td>{{ $value->employee_point }}</td>@endif
                        @if(isset($positionKey['mentor']))<td>{{ $value->mentor_point }}</td>@endif
                        @if(isset($positionKey['leader']))<td>{{ $value->leader_point }}</td>@endif
                        @if(isset($positionKey['pm']))<td>{{ $value->pm_point }}</td>@endif
                    </tr>
                @endforeach
                <!-- Add td total point -->
                <tr>
                    <td colspan="2">Tổng</td>
                    @if(isset($positionKey['member']))<td>{{ $content->sum('employee_point') }}</td>@endif
                    @if(isset($positionKey['mentor']))<td>{{ $content->sum('mentor_point') }}</td>@endif
                    @if(isset($positionKey['leader']))<td>{{ $content->sum('leader_point') }}</td>@endif
                    @if(isset($positionKey['pm']))<td>{{ $content->sum('pm_point') }}</td>@endif
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Employees answer the questions -->
    <div style="padding-top: 40px; font-weight: bold">
        <span>2. Đánh giá chi tiết</span>
    </div>
    <div style="padding-top: 10px">
        @foreach ($questions as $key1 => $value1)


            @if ($value1->type == 0)
                @if (!isset($memberDisplayed))
                    <br>
                    <span style="color: green; font-weight: bold">Member: {{ $value1->fullname }}</span>
                    <br>
                    <?php $memberDisplayed = true; ?>
                @endif
            @elseif ($value1->type == 0.5)
                @if (!isset($mentorDisplayed))
                    <br>
                    <span style="color: rgb(0, 195, 255); font-weight: bold">Mentor: {{ $value1->fullname }}</span>
                    <br>
                    <?php $mentorDisplayed = true; ?>
                @endif
            @elseif ($value1->type == 1)
                @if (!isset($leaderDisplayed))
                    <br>
                    <span style="color: blue; font-weight: bold">Leader: {{ $value1->fullname }}</span>
                    <br>
                    <?php $leaderDisplayed = true; ?>
                @endif
            @else
                @if (!isset($pmDisplayed))
                    <br>
                    <span style="color: orange; font-weight: bold">PM: {{ $value1->fullname }}</span>
                    <br>
                    <?php $pmDisplayed = true; ?>
                @endif
            @endif

            <br>
            <span style="font-weight: bold">{{ $key1+1 }}.{{ $value1->question }}</span>
            <br>
            <span>{!! html_entity_decode($value1->employee_answer) !!}</span>
            @foreach ($value1->reviewFiles as $file)
                <br>
                <img src="{{ public_path($file->file_path) }}" style="height: 270px">
            @endforeach
        @endforeach
    </div>

    @if ($comment->status_text)
        <br>
        <!-- Director comments -->
        <span style="font-weight: bold">Director</span>
        <br>
        <span>Trạng thái phê duyệt: {{ $comment->status_text }}</span>
        <br>
        <span>{!! html_entity_decode($comment->director_comment) !!}</span>
    @endif
</div>
@endsection

@section('title', 'Đánh giá nhân viên')

<style>
    body {
        font-family: 'DejaVu Sans', sans-serif !important;
    }
    table {
        border-collapse: collapse;
    }

    table td, table th {
        border: 1px solid black;
        text-align: center;
    }
</style>