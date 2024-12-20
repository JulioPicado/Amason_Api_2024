<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\cart_products;
use App\Models\product;
    use App\Models\category;
use App\Http\Controllers\StockManagementController;

class CartProductsController extends Controller
{

    public function getCategories()


    {
        $list = category::all(); // Asegúrate de usar el nombre correcto del modelo

        return response()->json($list); // Retornar como JSON
    }

    public function showCart(){
  

        $userId = auth()->id(); // Obtén el ID del usuario autenticado
        $listcartproducts = cart_products::where('user_id', $userId)->get();
        $totalamount = 0;
    
        // Agregando productos con detalles como nombre y precio
        $quantityofproductsincart = count($listcartproducts);
        foreach ($listcartproducts as $cartproduct) {
         //   $product = Product::where('product_id', $cartproduct->product_id)->firstOrFail();
         $product = $this->searchProduct($cartproduct->product_id);
            $cartproduct->product_name = $product->name;
            $cartproduct->product_price = $product->price;
            $cartproduct->product_description = $product->description;
            $cartproduct->stock = $product->stock;
            $cartproduct->discount = $product->discount;
            $cartproduct->total = $product->price * $cartproduct->quantity;
            $cartproduct->discount_amount = $cartproduct->total * $product->discount / 100;
            $cartproduct->total_with_discount = $cartproduct->total - ($cartproduct->total * $product->discount / 100);
            $cartproduct->product_image = $product->images->first()->image_path ?? 'default_image_path';
            $totalamount += $cartproduct->total_with_discount;
        }
    
        return response()->json([
            'cart_products' => $listcartproducts,
            'total_amount' => $totalamount,
            'quantityofproductsincart' => $quantityofproductsincart
        ]);
    }
    


    public function updateUnits(Request $request)
    {
        // Compactar los datos en un solo Requesto
        $data = new Request([
            'idproduct' => $request->input('idproducttoupdate'),
            'quantity' => $request->input('quantity'),
        ]);

        // Verificar la acción y llamar al método adecuado pasando el request
        if ($request->input('action') === 'add') {
            $this->addToCart($data);
        } elseif ($request->input('action') === 'remove') {
            $this->removeProductUnits($data);
        }
    }


    //todo    Metodo para agregar al carrito, se recibe un objeto cart_products, con quantity y product_id definidos, el user_id se obtiene de la session
    public function addToCart(Request $request)
    {
        $idproducttoadd = $request->input('idproduct');
        $quantitytoadd = $request->input('quantity');
        try {
            //*validar que exista el producto en el carrito
            $productincart = $this->searchProductInCart($idproducttoadd);

            //*validar que haya stock del producto
            //!se cambia la logica de un metodo
          $stock = $this->searchProduct($idproducttoadd)->stock;
            $quantity = $quantitytoadd;

            //* se verifica si hay mas stock del que se agrega
            if ($stock >= $quantity) {

                //*se verifica si ya existe ese producto en el carrito
                if ($productincart) {
                    //* se actualiza la cantidad

                    $productincart->quantity += $quantity;
                    if ($stock < $productincart->quantity) {
                        return response()->json(['error' => 'No hay suficiente stock disponible después de la actualización.'], 400);
                    }
                    
                    $productincart->save();
                } else {
                    $this->reduceStock($idproducttoadd, $quantitytoadd);
                   

                    $this->addProductToCart($idproducttoadd, $quantitytoadd);
                    return response()->json(['message' => 'El producto se ha agregado al carrito.'], 201);

                }

               
                $this->reduceStock($idproducttoadd, $quantitytoadd);
                //* mensaje caso exitoso
                return response()->json(['message' => 'Cantidad actualizada en el carrito.'], 200);
             
            } else {

                //! mensaje caso no hay stock
                return response()->json(['error' => 'No hay suficiente stock disponible.'], 400);
            }
            //* mensaje caso exitoso

        } catch (\Exception $e) {
            //! mensaje de error
            return response()->json(['error' => 'Ocurrió un error al agregar el producto al carrito: ' . $e->getMessage()], 500);
        }
    }
    public function searchProductInCart($idproducttoadd)
    {

        $productincart = cart_products::where('user_id', auth()->user()->id)->where('product_id', $idproducttoadd)->first();       

        return $productincart;
    }

    public function reduceStock($idproducttoadd, $quantitytoadd)
    {
        $stockController = new StockManagementController();
        $stockRequest = new Request([
            'product_id' => $idproducttoadd,
            'quantity_change' => $quantitytoadd,
        ]);
        $stockController->decreaseProductStock($stockRequest);
    }

    public function addStock($idproducttoremove, $quantitytoremove)
    {
        $stockController = new StockManagementController();
        $stockRequest = new Request([
            'product_id' => $idproducttoremove,
            'quantity_change' => $quantitytoremove,
        ]);
        $stockController->increaseProductStock($stockRequest);
    }



    public function searchProductInCartByuser_id()
    {

        $productincart = cart_products::where('user_id', auth()->user()->id)->get();
      

        return $productincart;
    }

    public function searchProduct($idproducttoadd)
    {
        $product = Product::where('product_id', $idproducttoadd)->firstOrFail();

        return $product;
    }

    public function addProductToCart($idproducttoadd, $quantitytoadd)
    {


      $product =  $this->searchProduct($idproducttoadd);

        $newproductincart = new cart_products([

            'user_id' =>  auth()->user()->id, 
            'product_id' => $idproducttoadd,
            'quantity' => $quantitytoadd,
            'discount' => $product->discount,
        ]);

        $newproductincart->save();

    }


    //todo    Metodo para remover un producto del carrito 
    public function removeProductUnits(Request $request)
    {
        try {
            $idproducttoremove = $request->input('idproduct');
            $quantitytoremove = $request->input('quantity');
            //* Buscar el producto en el carrito 
            $productincart = $this->searchProductInCart($idproducttoremove);

            //* Verificar que la cantidad en el carrito sea suficiente para restar
            if ($productincart->quantity >= $quantitytoremove) {

                $productincart->quantity -= $quantitytoremove;
                $this->addStock($idproducttoremove, $quantitytoremove);
                //* Si la cantidad es 0, eliminar el producto del carrito
                if ($productincart->quantity == 0) {
                    $productincart->delete();
                    return response()->json(['message' => 'El producto se ha eliminado del carrito.'], 200);
                }

                //* Guardar la nueva cantidad
                $productincart->save();
                //* mensaje caso exitoso
                return response()->json(['message' => 'Cantidad actualizada en el carrito.'], 200);
            } else {

                //! mensaje caso resultado negativo
                return response()->json(['error' => 'Cantidad a remover es mayor a la cantidad en el carrito.'], 400);
            }
        } catch (\Exception $e) {

            //! mensaje de error
            return response()->json(['error' => 'Ocurrió un error al intentar actualizar el carrito.'], 500);
        }
    }


    //todo    Metodo para remover un producto del carrito 
    public function removeProductFromCart(Request $request)
    {
        try {
            $idproducttoremove = $request->input('idproducttoremove');
            //* Buscar el producto en el carrito 
            //  $productincart = cart_products::where('user_id', auth()->user()->id)->where('product_id', $idproducttoremove)->firstOrFail();
            $productincart = $this->searchProductInCart($idproducttoremove);
            $this->addStock($idproducttoremove, $productincart->quantity);

            $productincart->delete();
            return response()->json(['message' => 'El producto se ha eliminado del carrito.'], 200);
        } catch (\Exception $e) {

            //! mensaje de error
            return response()->json(['error' => 'Ocurrió un error al intentar actualizar el carrito.'], 500);
        }
    }

    //todo    Metodo para remover un producto del carrito 
    public function removeAllProductsFromCart()  
    {
    try {
        $productsInCart = $this->searchProductInCartByuser_id();
        foreach ($productsInCart as $product) {
            $this->addStock($product->product_id, $product->quantity);
            $product->delete();
        }
        return response()->json(['message' => 'Los productos se han eliminado del carrito.'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Ocurrió un error al intentar actualizar el carrito.'], 500);
    }
}


}
