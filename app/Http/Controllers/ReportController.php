<?php
namespace App\Http\Controllers;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function getTopSellingProductsByStore(Request $request, $storeId)
    {
        // Validar las fechas de inicio y fin
        $validatedData = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $validatedData['start_date'];
        $endDate = $validatedData['end_date'];

        // Consultar los productos más vendidos en la tabla order_items en el rango de fechas
        $topSellingProducts = OrderItem::with('product')
            ->whereHas('product', function ($query) use ($storeId) {
                $query->where('store_id', $storeId);
            })
            ->whereBetween('created_at', [$startDate, $endDate]) // Filtrar por fecha en order_items
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(10) // Limitar a los 10 productos más vendidos
            ->get()
            ->map(function ($orderItem) {
                return [
                    'product_id' => $orderItem->product_id,
                    'name' => $orderItem->product->name,
                    'description' => $orderItem->product->description,
                    'total_sold' => $orderItem->total_sold,
                ];
            });

        // Verificar si se encontraron productos
        if ($topSellingProducts->isEmpty()) {
            return response()->json(['message' => 'No se han encontrado productos vendidos en el período seleccionado.'], 404);
        }

        return response()->json($topSellingProducts);
    }
}
