<?php

namespace App\Http\Controllers;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Traits\DatabaseAgnosticSearch;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    use DatabaseAgnosticSearch;

    /**
     * List all activity logs with advanced filtering
     * 
     * GET /api/activity-logs
     * 
     * Query Parameters:
     * - event: created, updated, deleted
     * - subject_type: Model class name (e.g., Order, Product, Customer)
     * - subject_id: ID of the subject
     * - causer_type: Employee, Customer
     * - causer_id: ID of the person who caused the activity
     * - log_name: Table name
     * - date_from: Start date (YYYY-MM-DD)
     * - date_to: End date (YYYY-MM-DD)
     * - search: Search in description
     * - sort_by: created_at, event, subject_type (default: created_at)
     * - sort_direction: asc, desc (default: desc)
     * - per_page: Items per page (default: 50)
     */
    public function index(Request $request)
    {
        $query = Activity::query()
            ->with(['causer', 'subject']);

        // Filter by event type (created, updated, deleted)
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filter by subject type (model)
        if ($request->filled('subject_type')) {
            $subjectType = $request->subject_type;
            
            // Allow short names like "Order" instead of full "App\Models\Order"
            if (!str_contains($subjectType, '\\')) {
                $subjectType = "App\\Models\\{$subjectType}";
            }
            
            $query->where('subject_type', $subjectType);
        }

        // Filter by subject ID
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        // Filter by causer type (Employee, Customer)
        if ($request->filled('causer_type')) {
            $causerType = $request->causer_type;
            
            if (!str_contains($causerType, '\\')) {
                $causerType = "App\\Models\\{$causerType}";
            }
            
            $query->where('causer_type', $causerType);
        }

        // Filter by causer ID (specific user)
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        // Filter by log name (table name)
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in description
        if ($request->filled('search')) {
            $this->whereLike($query, 'description', $request->search);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        $allowedSortFields = ['created_at', 'event', 'subject_type', 'causer_type', 'log_name'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderByDesc('created_at');
        }

        // Pagination
        $perPage = $request->get('per_page', 50);
        $logs = $query->paginate($perPage);

        // Transform data for better FE consumption
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'log_name' => $log->log_name,
                
                // Subject (WHAT was changed)
                'subject' => [
                    'type' => class_basename($log->subject_type),
                    'full_type' => $log->subject_type,
                    'id' => $log->subject_id,
                    'data' => $log->subject,  // The actual model instance (if not deleted)
                ],
                
                // Causer (WHO made the change)
                'causer' => [
                    'type' => $log->causer_type ? class_basename($log->causer_type) : null,
                    'full_type' => $log->causer_type,
                    'id' => $log->causer_id,
                    'name' => $log->causer ? ($log->causer->name ?? $log->causer->email ?? 'Unknown') : 'System',
                ],
                
                // Changes (WHAT changed)
                'changes' => [
                    'attributes' => $log->properties->get('attributes', []),
                    'old' => $log->properties->get('old', []),
                ],
                
                // Metadata
                'metadata' => [
                    'ip_address' => $log->properties->get('ip_address'),
                    'user_agent' => $log->properties->get('user_agent'),
                    'url' => $log->properties->get('url'),
                    'method' => $log->properties->get('method'),
                ],
                
                // WHEN
                'created_at' => $log->created_at->toIso8601String(),
                'created_at_human' => $log->created_at->diffForHumans(),
                'created_at_formatted' => $log->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json($logs);
    }

    /**
     * Get details of a specific activity log
     * 
     * GET /api/activity-logs/{id}
     */
    public function show($id)
    {
        $log = Activity::with(['causer', 'subject'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'log_name' => $log->log_name,
                'batch_uuid' => $log->batch_uuid,
                
                'subject' => [
                    'type' => class_basename($log->subject_type),
                    'full_type' => $log->subject_type,
                    'id' => $log->subject_id,
                    'data' => $log->subject,
                ],
                
                'causer' => [
                    'type' => $log->causer_type ? class_basename($log->causer_type) : null,
                    'full_type' => $log->causer_type,
                    'id' => $log->causer_id,
                    'name' => $log->causer ? ($log->causer->name ?? $log->causer->email ?? 'Unknown') : 'System',
                    'data' => $log->causer,
                ],
                
                'properties' => $log->properties,
                
                'changes' => [
                    'attributes' => $log->properties->get('attributes', []),
                    'old' => $log->properties->get('old', []),
                ],
                
                'metadata' => [
                    'ip_address' => $log->properties->get('ip_address'),
                    'user_agent' => $log->properties->get('user_agent'),
                    'url' => $log->properties->get('url'),
                    'method' => $log->properties->get('method'),
                ],
                
                'created_at' => $log->created_at->toIso8601String(),
                'created_at_human' => $log->created_at->diffForHumans(),
                'created_at_formatted' => $log->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $log->updated_at->toIso8601String(),
            ]
        ]);
    }

    /**
     * Get statistics for activity logs
     * 
     * GET /api/activity-logs/statistics
     */
    public function getStatistics(Request $request)
    {
        $query = Activity::query();

        // Apply same filters as index
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        } else {
            // Default to last 30 days
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $stats = [
            'total_activities' => (clone $query)->count(),
            'by_event' => (clone $query)->selectRaw('event, COUNT(*) as count')
                ->groupBy('event')
                ->pluck('count', 'event')
                ->toArray(),
            'by_model' => (clone $query)->selectRaw('subject_type, COUNT(*) as count')
                ->groupBy('subject_type')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'model' => class_basename($item->subject_type),
                    'count' => $item->count
                ])
                ->toArray(),
            'by_user' => (clone $query)->selectRaw('causer_type, causer_id, COUNT(*) as count')
                ->whereNotNull('causer_id')
                ->groupBy('causer_type', 'causer_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    $causer = app($item->causer_type)->find($item->causer_id);
                    return [
                        'user' => $causer ? ($causer->name ?? $causer->email) : 'Unknown',
                        'type' => class_basename($item->causer_type),
                        'count' => $item->count
                    ];
                })
                ->toArray(),
            'today' => (clone $query)->whereDate('created_at', today())->count(),
            'this_week' => (clone $query)->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get activity logs for a specific model instance
     * 
     * GET /api/activity-logs/model/{model}/{id}
     * Example: /api/activity-logs/model/Order/123
     */
    public function getModelLogs($modelType, $modelId)
    {
        // Allow short names
        if (!str_contains($modelType, '\\')) {
            $modelType = "App\\Models\\{$modelType}";
        }

        $logs = Activity::where('subject_type', $modelType)
            ->where('subject_id', $modelId)
            ->with(['causer'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event' => $log->event,
                    'description' => $log->description,
                    'causer' => [
                        'type' => $log->causer_type ? class_basename($log->causer_type) : null,
                        'name' => $log->causer ? ($log->causer->name ?? $log->causer->email ?? 'Unknown') : 'System',
                    ],
                    'changes' => [
                        'attributes' => $log->properties->get('attributes', []),
                        'old' => $log->properties->get('old', []),
                    ],
                    'created_at' => $log->created_at->toIso8601String(),
                    'created_at_human' => $log->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Export activity logs to CSV
     * 
     * GET /api/activity-logs/export/csv
     */
    public function exportCsv(Request $request)
    {
        $query = Activity::query()->with(['causer']);

        // Apply same filters as index
        $this->applyFilters($query, $request);

        // Limit export to prevent memory issues
        $logs = $query->limit(10000)->get();

        $filename = 'activity-logs-' . now()->format('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'ID',
                'Event',
                'Description',
                'Model',
                'Model ID',
                'User Type',
                'User Name',
                'IP Address',
                'Date Time',
            ]);

            // CSV Rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->event,
                    $log->description,
                    class_basename($log->subject_type),
                    $log->subject_id,
                    $log->causer_type ? class_basename($log->causer_type) : 'System',
                    $log->causer ? ($log->causer->name ?? $log->causer->email ?? 'Unknown') : 'System',
                    $log->properties->get('ip_address', 'N/A'),
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export activity logs to Excel (using CSV with proper headers)
     * 
     * GET /api/activity-logs/export/excel
     */
    public function exportExcel(Request $request)
    {
        $query = Activity::query()->with(['causer']);

        // Apply same filters as index
        $this->applyFilters($query, $request);

        $logs = $query->limit(10000)->get();

        $filename = 'activity-logs-' . now()->format('Y-m-d-His') . '.xlsx';
        
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        // For simplicity, using CSV with Excel headers
        // In production, consider using maatwebsite/excel package
        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'ID',
                'Event',
                'Description',
                'Model',
                'Model ID',
                'User Type',
                'User Name',
                'IP Address',
                'URL',
                'Method',
                'Date Time',
                'Changes',
            ]);

            foreach ($logs as $log) {
                $old = $log->properties->get('old', []);
                $new = $log->properties->get('attributes', []);
                $changes = [];
                
                foreach ($new as $key => $value) {
                    if (isset($old[$key]) && $old[$key] != $value) {
                        $changes[] = "{$key}: {$old[$key]} → {$value}";
                    }
                }
                
                fputcsv($file, [
                    $log->id,
                    $log->event,
                    $log->description,
                    class_basename($log->subject_type),
                    $log->subject_id,
                    $log->causer_type ? class_basename($log->causer_type) : 'System',
                    $log->causer ? ($log->causer->name ?? $log->causer->email ?? 'Unknown') : 'System',
                    $log->properties->get('ip_address', 'N/A'),
                    $log->properties->get('url', 'N/A'),
                    $log->properties->get('method', 'N/A'),
                    $log->created_at->format('Y-m-d H:i:s'),
                    implode('; ', $changes),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Apply filters to query (reusable for export)
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('subject_type')) {
            $subjectType = $request->subject_type;
            if (!str_contains($subjectType, '\\')) {
                $subjectType = "App\\Models\\{$subjectType}";
            }
            $query->where('subject_type', $subjectType);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('causer_type')) {
            $causerType = $request->causer_type;
            if (!str_contains($causerType, '\\')) {
                $causerType = "App\\Models\\{$causerType}";
            }
            $query->where('causer_type', $causerType);
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $this->whereLike($query, 'description', $request->search);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Get unique model types for filtering
     * 
     * GET /api/activity-logs/models
     */
    public function getAvailableModels()
    {
        $models = Activity::selectRaw('DISTINCT subject_type')
            ->whereNotNull('subject_type')
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->map(fn($type) => [
                'label' => class_basename($type),
                'value' => class_basename($type),
                'full_name' => $type,
            ])
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $models
        ]);
    }

    /**
     * Get unique users for filtering
     * 
     * GET /api/activity-logs/users
     */
    public function getAvailableUsers()
    {
        $users = Activity::selectRaw('DISTINCT causer_type, causer_id')
            ->whereNotNull('causer_id')
            ->get()
            ->map(function($item) {
                $causer = app($item->causer_type)->find($item->causer_id);
                return [
                    'label' => $causer ? ($causer->name ?? $causer->email) : 'Unknown',
                    'value' => $item->causer_id,
                    'type' => class_basename($item->causer_type),
                ];
            })
            ->unique('value')
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}
