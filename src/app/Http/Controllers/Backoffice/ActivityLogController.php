<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $allowedActions = ['created', 'updated', 'duplicated', 'duplicate_source_used', 'deleted', 'restored'];
        $action = trim((string) $request->query('action', ''));

        $activityLogsQuery = ActivityLog::with('user')
            ->where('entity_type', 'product')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (in_array($action, $allowedActions, true)) {
            $activityLogsQuery->where('action', $action);
        } else {
            $action = '';
        }

        $activityLogs = $activityLogsQuery
            ->paginate(20)
            ->withQueryString();

        $productIds = $activityLogs->pluck('entity_id')
            ->filter()
            ->unique()
            ->values();

        $activeProducts = Product::withTrashed()
            ->whereIn('id', $productIds)
            ->get(['id', 'name', 'code', 'deleted_at'])
            ->keyBy('id');

        $activityItems = $activityLogs->getCollection()->map(function (ActivityLog $log) use ($activeProducts) {
            $activeProduct = $activeProducts->get($log->entity_id);

            if ($activeProduct) {
                return [
                    'log' => $log,
                    'name' => $activeProduct->name,
                    'code' => $activeProduct->code,
                    'is_deleted' => $activeProduct->trashed(),
                ];
            }

            $meta = $this->extractProductMetaFromLog($log);

            return [
                'log' => $log,
                'name' => $meta['name'] ?: 'Producto eliminado',
                'code' => $meta['code'] ?: ('ID ' . $log->entity_id),
                'is_deleted' => true,
            ];
        });

        return view('backoffice.activity.index', compact(
            'activityLogs',
            'activityItems',
            'action'
        ));
    }

    public function productHistory(int $productId): View
    {
        $activityLogsQuery = ActivityLog::with('user')
            ->where('entity_type', 'product')
            ->where('entity_id', $productId)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $activityLogs = $activityLogsQuery
            ->paginate(15)
            ->withQueryString();

        abort_if($activityLogs->isEmpty(), 404);

        $product = Product::withTrashed(['categories', 'images'])->find($productId);

        if ($product) {
            $productMeta = [
                'name' => $product->name,
                'code' => $product->code,
                'is_deleted' => $product->trashed(),
            ];
        } else {
            $productMeta = $this->extractProductMetaFromLogs(
                (clone $activityLogsQuery)->get()
            );
            $productMeta['is_deleted'] = true;
        }

        $totalActivities = (clone $activityLogsQuery)->count();

        return view('backoffice.activity.product-history', compact(
            'productId',
            'product',
            'productMeta',
            'activityLogs',
            'totalActivities'
        ));
    }

    private function extractProductMetaFromLog(ActivityLog $log): array
    {
        return $this->extractProductMetaFromChanges($log->changes, $log->entity_id);
    }

    private function extractProductMetaFromLogs($logs): array
    {
        foreach ($logs as $log) {
            $meta = $this->extractProductMetaFromChanges($log->changes, $log->entity_id);

            if (!empty($meta['name']) || !empty($meta['code'])) {
                return $meta;
            }
        }

        $entityId = $logs->first()?->entity_id;

        return [
            'name' => 'Producto eliminado',
            'code' => $entityId ? ('ID ' . $entityId) : 'Sin código',
        ];
    }

    private function extractProductMetaFromChanges(?array $changes, ?int $defaultId = null): array
    {
        $snapshot = null;

        if (isset($changes['after']) && is_array($changes['after'])) {
            $snapshot = $changes['after'];
        } elseif (isset($changes['before']) && is_array($changes['before'])) {
            $snapshot = $changes['before'];
        }

        return [
            'name' => is_array($snapshot) ? (string) ($snapshot['name'] ?? '') : '',
            'code' => is_array($snapshot)
                ? (string) ($snapshot['code'] ?? '')
                : ($defaultId ? 'ID ' . $defaultId : ''),
        ];
    }
}