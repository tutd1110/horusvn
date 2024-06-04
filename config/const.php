<?php

/**
 * constant file
 */
return [
    'productions_connection' => 'horusvn_productions',
    'ai_connection' => 'horusvn_ai',
    'permissions' => [
        0 => 'Nhân viên',
        1 => 'Admin',
        // 2 => 'Leader',
        // 3 => 'Manager'
    ],
    'departments' => [
        1 => 'Admin',
        2 => 'Dev',
        3 => 'Game Design',
        4 => 'Art',
        5 => 'Tester',
        6 => 'Điều hành',
        7 => 'Hành chính nhân sự',
        8 => 'Kế toán',
        9 => 'Phân tích dữ liệu',
        10 => 'Support',
        11 => 'Marketing',
        13 => 'Video Editor',
        12 => 'AI Engineer',
    ],
    'departments_with_job' => [
        [
            "value" => 1,
            "label" => "Admin",
            "short_name" => "admin"
        ],
        [
            "value" => 2,
            "label" => "Dev",
            "short_name" => "dev"
        ],
        [
            "value" => 3,
            "label" => "Game Design",
            "short_name" => "gd"
        ],
        [
            "value" => 4,
            "label" => "Art",
            "short_name" => "art"
        ],
        [
            "value" => 5,
            "label" => "Tester",
            "short_name" => "test"
        ],
        [
            "value" => 9,
            "label" => "Phân tích dữ liệu",
            "short_name" => "analysis"
        ],
        [
            "value" => 11,
            "label" => "Marketing",
            "short_name" => "marketing"
        ],
        [
            "value" => 13,
            "label" => "Video Editor",
            "short_name" => "ve"
        ],
        [
            "value" => 12,
            "label" => "AI Engineer",
            "short_name" => "ai"
        ],
        [
            "value" => 7,
            "label" => "Hành chính nhân sự",
            "short_name" => "hr"
        ],
    ],
    'positions' => [
        0 => "Nhân viên",
        1 => 'Leader',
        2 => 'Quản lý',
        3 => 'Giám đốc',
    ],
    'user_status' => [
        [
            "value" => 1,
            "label" => "Hoạt động"
        ],
        [
            "value" => 2,
            "label" => "Nghỉ việc"
        ],
    ],
    'type_check' => [
        [
            "value" => 1,
            "label" => "Camera AI"
        ],
        [
            "value" => 2,
            "label" => "Thủ công"
        ],
    ],
    //tasks status
    'status' => [
        [
            "value" => 7,
            "label" => "------------"
        ],
        [
            "value" => 0,
            "label" => "Quá hạn"
        ],
        [
            "value" => 1,
            "label" => "Đang chờ"
        ],
        [
            "value" => 2,
            "label" => "Đang tiến hành"
        ],
        [
            "value" => 3,
            "label" => "Tạm dừng"
        ],
        [
            "value" => 4,
            "label" => "Hoàn thành"
        ],
        [
            "value" => 5,
            "label" => "Chờ feedback"
        ],
        [
            "value" => 6,
            "label" => "Làm lại"
        ],
        [
            "value" => 8,
            "label" => "Dừng hoàn toàn"
        ],
        [
            "value" => 9,
            "label" => "Đang sửa bug"
        ]
    ],
    //parent/child tasks label
    'type_of_task' => [
        [
            "value" => "parent",
            "label" => "Công việc cha"
        ],
        [
            "value" => "child",
            "label" => "Công việc con"
        ]
    ],
    //petition type
    'petition_type' => [
        [
            "id" => 1,
            "name" => "Đi muộn/về sớm"
        ],
        [
            "id" => 2,
            "name" => "Nghỉ phép"
        ],
        [
            "id" => 3,
            "name" => "Nghỉ việc"
        ],
        [
            "id" => 4,
            "name" => "Thay đổi giờ công"
        ],
        [
            "id" => 5,
            "name" => "Đăng ký làm công"
        ],
        [
            "id" => 6,
            "name" => "Đăng ký làm nỗ lực"
        ],
        [
            "id" => 7,
            "name" => "Đăng ký ra ngoài"
        ],
        [
            "id" => 8,
            "name" => "Thay đổi giờ ra ngoài"
        ],
        [
            "id" => 9,
            "name" => "Đi công tác"
        ]
    ],
    'petition_time_period' => [
        [
            "id" => 1,
            "name" => "Buổi sáng"
        ],
        [
            "id" => 2,
            "name" => "Buổi chiều"
        ],
        [
            "id" => 3,
            "name" => "Một ngày"
        ],
        [
            "id" => 4,
            "name" => "Nhiều ngày"
        ]
    ],
    //petition type paid
    'type_go_out' => [
        [
            "id" => 0,
            "name" => "Việc công ty"
        ],
        [
            "id" => 1,
            "name" => "Việc cá nhân"
        ],
        [
            "id" => 2,
            "name" => "Trong thời gian Warrior"
        ],
    ],
    //petition type paid
    'type_paid' => [
        [
            "id" => 0,
            "name" => "Không lương"
        ],
        [
            "id" => 1,
            "name" => "Có lương"
        ]
    ],
    //type delete task parent
    'type_delete_task' => [
        0 => "",
        1 => 'Delete parent/child',
        2 => 'Delete parent'
    ],
    //task_assignments table status
    'task_assignments_status' => [
        [
            "value" => 0,
            "label" => "NEW"
        ],
        [
            "value" => 1,
            "label" => "OPEN"
        ],
        [
            "value" => 3,
            "label" => "CNR"
        ],
        [
            "value" => 6,
            "label" => "NAB"
        ],
        [
            "value" => 4,
            "label" => "TFU"
        ],
        [
            "value" => 2,
            "label" => "FIXED"
        ],
        [
            "value" => 5,
            "label" => "CONFIRMED"
        ]
    ],
    //task_assignments table tag_test's status
    'task_assignments_tag_test' => [
        [
            "value" => 0,
            "label" => "Test trước done"
        ],
        [
            "value" => 1,
            "label" => "Test sau done"
        ],
        [
            "value" => 2,
            "label" => "Test sau upgame"
        ],
        [
            "value" => 3,
            "label" => "Back Test"
        ],
    ],
    //task_assignments level
    'task_assignments_level' => [
        [
            "value" => 0,
            "label" => "Thấp"
        ],
        [
            "value" => 1,
            "label" => "Cao"
        ],
    ],
    //review statuses
    'review_progresses' => [
        '0' => 'Waiting Member',
        '0.5' => 'Waiting Mentor',
        '1' => 'Waiting Leader',
        '2' => 'Waiting PM',
        '3' => 'Waiting Director',
        '4' => 'Completed'
    ],
    //review period
    'review_period' => [
        0 => '2 Weeks',
        1 => '2 Months',
        2 => '6 Months',
        3 => '1 Years',
        4 => 'Hết học việc'
    ],
    //review statuses
    'review_statuses' => [
        0 => 'Approved',
        1 => 'Rejected'
    ],
    //task_assignment type
    'task_assignment_type' => [
        [
            "value" => 0,
            "label" => "Bug"
        ],
        [
            "value" => 1,
            "label" => "Feedback"
        ],
        [
            "value" => 2,
            "label" => "Add Design"
        ],
        [
            "value" => 3,
            "label" => "Edit Design"
        ]
    ],
    //task_timings type
    'task_timings_type' => [
        [
            "value" => 0,
            "label" => "Task"
        ],
        [
            "value" => 1,
            "label" => "Bug"
        ],
        [
            "value" => 2,
            "label" => "Feedback"
        ],
        [
            "value" => 3,
            "label" => "Add Design"
        ],
        [
            "value" => 4,
            "label" => "Edit Design"
        ]
    ],
    'games' => [
        [
            "value" => 1,
            "label" => "World War - Android"
        ],
        [
            "value" => 4,
            "label" => "World War - IOS"
        ],
        [
            "value" => 2,
            "label" => "War Zone - Android"
        ],
        [
            "value" => 3,
            "label" => "Beach Defense - Android"
        ],
        [
            "value" => 5,
            "label" => "Sky Defense - Android"
        ],
    ],
    'violation_type' => [
        [
            "value" => 1,
            "label" => "Vi phạm thời gian làm việc"
        ],
        [
            "value" => 2,
            "label" => "Không chấp hành mệnh lệnh cấp trên"
        ],
        [
            "value" => 3,
            "label" => "Không tuân thủ quy trình được hướng dẫn"
        ],
        [
            "value" => 4,
            "label" => "Không tuân thủ nội quy, quy định của công ty"
        ],
        [
            "value" => 5,
            "label" => "Hành vi trộm cắp, tham ô, gây rối, phá hoại công ty."
        ],
        [
            "value" => 6,
            "label" => "Vi phạm về bảo vệ tài sản, bí mật công nghệ, kinh doanh"
        ],
    ],
    'calendar_colors' => [
        [
            "label" => "Màu 1",
            "key" => '#FF3838',
            "value" => 1
        ],
        [
            "label" => "Màu 2",
            "key" => '#800000',
            "value" => 2
        ],
        [
            "label" => "Màu 3",
            "key" => '#0b8043',
            "value" => 3
        ],
        [
            "label" => "Màu 4",
            "key" => '#039be5',
            "value" => 4
        ],
        [
            "label" => "Màu 5",
            "key" => '#8e24aa',
            "value" => 5
        ],
        [
            "label" => "Màu 6",
            "key" => '#E67C73',
            "value" => 6
        ],
        [
            "label" => "Màu 7",
            "key" => '#616161',
            "value" => 7
        ],
        [
            "label" => "Màu 8",
            "key" => '#3f51b5',
            "value" => 8
        ],
    ],
    'user_type' => [
        4 => 'Cộng tác viên',
        1 => 'Chính thức',
        2 => 'Thử việc',
        3 => 'Học việc'
    ],
    'warror_title' => [
        1 => 'Soldier',
        2 => 'Warrior 1',
        3 => 'Warrior 2',
        4 => 'Warrior 3'
    ],
    'calendar_status' => [
        [
            "value" => 0,
            "label" => "Toàn công ty"
        ],
        [
            "value" => 1,
            "label" => "Bộ phận"
        ],
        [
            "value" => 2,
            "label" => "Quản lý"
        ],
        [
            "value" => 3,
            "label" => "Cá nhân"
        ]
    ],
    'gender' => [
        [
            "value" => 'male',
            "label" => "Nam"
        ],
        [
            "value" => 'female',
            "label" => "Nữ"
        ],
        
    ],
    'view_status' => [
        [
            "value" => 0,
            "label" => "Toàn bộ"
        ],
        [
            "value" => 1,
            "label" => "Admin"
        ],
    ],
    'tracking_game' => [
        [
            "value" => 1,
            "label" => "World War",
            "url" => "http://bw-analytic.horusvn.com/"
        ],
        [
            "value" => 2,
            "label" => "War Zone",
            "url" => "http://wz-analytic.horusvn.com/"
        ],
        [
            "value" => 3,
            "label" => "Tracking IAP",
            "url" => "https://lookerstudio.google.com/u/0/reporting/16673e0c-0cb8-4f5f-9c81-fdbe42b5cfe7/page/p_w8xxufj29c/edit"
        ],
        
    ],
    'purchase_type' => [
        [
            "value" => 1,
            "label" => "Gia công",
        ],
        [
            "value" => 2,
            "label" => "Mua hàng",
        ],
    ],
    //role
    'employee_id_leader_roles' => [45,46,49,51,52,63,69,107,161,232],
    'employee_id_pm_roles' => [45,46,49,51,63,107,161,232,136,82],
    'petitions_full_permission' => [46,51,63,82,90,107,161,232,183],
    'employee_id_phone_view' => [46,51,63,82,90,107,161,232],
    'task_assignments_edit_role' => [47,51,52,107,161,232],
    'employee_screen_role' => [46,51,63,82,107,161,232, 99],
    'employee_pm_notifications' => [45,46,49,51,63,82,107,161,232,136],
    //all reviewees who under director's control
    'director_reviewees' => [63,82,90],
    //total hours if employee wanna create a petition while off below a day without salary
    'hour_without_salary' => 48,
    //total hours if employee wanna create a petition while off below a day with salary
    'hour_with_salary' => 120,
    //total hours if employee wanna create a petition while off in many days without salary
    'hour_days_without_salary' => 168,
    //total hours if employee wanna create a petition while off in many days with salary
    'hour_days_with_salary' => 336,
    //start work time AM
    'start_time_am' => "08:00:00",
    //end work time AM
    'end_time_am' => "12:00:00",
    //start work time PM
    'start_time_pm' => "13:30:00",
    //end work time PM
    'end_time_pm' => "17:30:00",
    //detect if employee come late or offline early from 11:00:00AM
    'time_detected' => '11:00:00',
    'image_extension_list_base' => env('IMAGE_EXTENSION_LIST_BASE'),
    'avatar_file_folder' => env('AVATAR_FILE_FOLDER'),
    'post_file_folder' => env('POST_FILE_FOLDER'),
    'journal_file_folder' => env('JOURNAL_FILE_FOLDER'),
    'review_file_folder' => env('REVIEW_FILE_FOLDER'),
    'task_file_folder' => env('TASK_FILE_FOLDER'),
    'violation_file_folder' => env('VIOLATION_FILE_FOLDER'),
    'avatar_sample_path' => env('AVATAR_SAMPLE_PATH'),
    'avatar_size_max' => env('AVATAR_SIZE_MAX'),
    'employee_id_edit_calendar' => [82,107,161,232,63,99,51,90,152,183],
    'employee_id_view_all_calendar' => [82,183],
    'super_admin' => [107,161,232,63,82],
    'employee_add_permission' => [55,56,83,96,127],
];
