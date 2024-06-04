<?php

namespace App\Http\Controllers\api\Export;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Review;
use App\Models\EmployeeReviewPoint;
use App\Models\QuestionReview;
use App\Models\EmployeeAnswer;
use App\Models\ContentReview;
use App\Models\ReviewComment;
use Carbon\Carbon;
use Storage;
use File;

/**
 * Employee Review Export API
 *
 * @group Employee Review Export
 */
class EmployeeReviewExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            set_time_limit(300);

            //on request
            $requestDatas = $request->all();

            //get review
            $review = Review::select('id', 'period', 'employee_id', 'start_date')
                ->where('id', $requestDatas['id'])
                ->first();

            if (!$review) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => __('MSG-E-003')
                    ], Response::HTTP_NOT_FOUND);
            }

            //get group employee for the review
            $employee = User::select('id', 'department_id', 'fullname', 'position')
                        ->where('id', $review->employee_id)
                        ->first();

            $employees = User::select('id', 'fullname', 'position')
            ->where('id', $employee->id)
            ->orWhere(function ($query) use ($employee) {
                $query->where('department_id', $employee->department_id)
                    ->where('position', 1);
            })
            ->orWhere(function ($query) use ($employee) {
                $query->where('position', 2);
            })
            ->get();
            //end

            //get content review
            $content = EmployeeReviewPoint::join('content_reviews', function ($join) {
                $join->on('content_reviews.id', '=', 'employee_review_points.content_review_id')
                    ->whereNull('content_reviews.deleted_at');
            })
            ->selectRaw(DB::raw("
                employee_review_points.id as id,
                content_reviews.content as content,
                employee_review_points.employee_point as employee_point,
                employee_review_points.leader_point as leader_point,
                employee_review_points.pm_point as pm_point"))
            ->where('employee_review_points.review_id', $review->id)
            ->get();
            //end

            //get review questions
            $questions = EmployeeAnswer::join('question_reviews', function ($join) {
                $join->on('question_reviews.id', '=', 'employee_answers.question_review_id')
                    ->whereNull('question_reviews.deleted_at');
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'employee_answers.employee_id')
                    ->whereNull('users.deleted_at');
            })
            ->select(
                'employee_answers.id as id',
                'employee_answers.employee_id as employee_id',
                'question_reviews.question as question',
                'users.fullname as fullname',
                'employee_answers.employee_answer as employee_answer',
                'employee_answers.type as type'
            )
            ->where('employee_answers.review_id', $review->id)
            ->orderByRaw('users.position asc, question_reviews.id asc')
            ->get();
            //end

            //Load template excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
                resource_path('templates\employee_review_template.xlsx')
            );
            //set value
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle('Review');

            //print data to template
            $this->setExcelData($worksheet, "A", 5, "Nhân viên: ".$employee->fullname, true);

            $startDate = Carbon::parse($review->start_date)->format('d/m/Y');
            $this->setExcelData($worksheet, "E", 6, "Ngày đánh giá: ".$startDate, true);

            foreach ($employees as $item) {
                if ($item->position == 0) {
                    $this->setExcelData($worksheet, "E", 9, $item->fullname, true);
                } elseif ($item->position == 1) {
                    $this->setExcelData($worksheet, "F", 9, $item->fullname, true);
                } elseif ($item->position == 2) {
                    $this->setExcelData($worksheet, "G", 9, $item->fullname, true);
                }
            }

            $totalEmployeePoint = 0;
            $totalLeaderPoint = 0;
            $totalPmPoint = 0;
            foreach ($content as $key => $value) {
                $this->setExcelData($worksheet, "A", $key+10, $key+1);

                $this->setExcelData($worksheet, "B", $key+10, $value->content);

                if ($value->employee_point) {
                    $totalEmployeePoint += $value->employee_point;
                    $this->setExcelData($worksheet, "E", $key+10, $value->employee_point);
                }
                if ($value->leader_point) {
                    $totalLeaderPoint += $value->leader_point;
                    $this->setExcelData($worksheet, "F", $key+10, $value->leader_point);
                }
                if ($value->pm_point) {
                    $totalPmPoint += $value->pm_point;
                    $this->setExcelData($worksheet, "G", $key+10, $value->pm_point);
                }
            }

            //print total point
            $this->setExcelData($worksheet, "D", count($content)+10, 'Tổng', true);

            if ($employee->position == 0) {
                $this->setExcelData($worksheet, "E", count($content)+10, $totalEmployeePoint, true);
            }
            $this->setExcelData($worksheet, "F", count($content)+10, $totalLeaderPoint, true);
            $this->setExcelData($worksheet, "G", count($content)+10, $totalPmPoint, true);
            //end

            $i = 0;
            //print questions and answers
            foreach ($questions as $key1 => $value1) {
                $row = 33+$key1+$i;

                $worksheet->mergeCells("A".($row).":G".($row));
                $question = ($key1+1).".".$value1->question." (".$value1->fullname.")";

                $worksheet->getRowDimension($row)->setRowHeight(($row));
                $worksheet->getStyle('A'.$row)->getAlignment()->setWrapText(true);

                $this->setExcelData($worksheet, "A", $row, $question, true);

                // Create a new rich text object
                $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();

                if ($value1->employee_answer) {
                    $dom = new \DOMDocument();
                    $dom->loadHTML($value1->employee_answer);

                    $htmlNode = $dom->getElementsByTagName('html')->item(0);
                    $bodyNode = $htmlNode->getElementsByTagName('body')->item(0);

                    $isFirstListItem = true;
                    // Loop through each DOMNode in the document
                    foreach ($bodyNode->childNodes as $childNode) {
                        // If the node is a <p> element
                        if ($childNode->nodeName == 'p') {
                            // If this is not the first list item, add a new line
                            if (!$isFirstListItem) {
                                $richText->createText("\n");
                            }
                            $richText->createText($childNode->nodeValue);
                            // Set the flag to false after the first list item is processed
                            $isFirstListItem = false;
                        } elseif ($childNode->nodeName === 'ol' || $childNode->nodeName === 'ul') {
                            foreach ($childNode->getElementsByTagName('li') as $index => $liNode) {
                                // If this is not the first list item, add a new line
                                if (!$isFirstListItem || $index > 0) {
                                    $richText->createText("\n");
                                }
                                $richText->createText(($index+1) . '. ' . $liNode->nodeValue . "\n");
                            }
                            // Set the flag to false after the first list item is processed
                            $isFirstListItem = false;
                        }
                    }

                    $worksheet->mergeCells("A".($row+1).":G".($row+1));


                    $worksheet->getRowDimension($row+1)->setRowHeight(
                        14.5 * (substr_count($richText, "\n") + 1)
                    );

                    $worksheet->getStyle('A'.$row+1)->getAlignment()->setWrapText(true);

                    $worksheet->getCell('A'.$row+1)->setValue($richText);
                } else {
                    $worksheet->getCell('A'.$row+1)->setValue($richText);
                }

                $i++;
            }

            //End
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'horus_review_'.time().'.xlsx';
            $filePath = Storage::path('excels/'.$filename);
            
            //Save file
            $writer->save($filePath);

            return response()->download($filePath);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    private function setExcelData($worksheet, $col, $row, $data, $bold = false)
    {
        $worksheet->getCell($col . $row)->setValueExplicit($data, "s");
        $style = $worksheet->getStyle($col . $row);
        $style->getFont()->setName('BIZ UD????')->getColor()->setRGB('000000');
        $style->getFont()->setSize(10)->setBold($bold);
    }
}
