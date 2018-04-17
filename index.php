<?php
    $json_file_path = "./data.json";
    $file_db;
    init();
    function init(){
        createXMLFileIfExists();
        if(isDeleteFacturaButtonTriggered()){
            $factura_id = $_POST['remover-factura'];
            deleteFacturaById($factura_id); //It deletes all the products associate to it and then remove it from Facturas table
        }
        else if(isDeleteProductoButtonTriggered()){
            $producto_id = $_POST['producto_id'];
            $result = deleteProductoById($producto_id); //It update the bill's taxes and total fields as well
        }
        else if(isSaveButtonTriggeredWithValues()){
            $cliente_nm = $_POST['cliente_nm'];
            $fecha = $_POST['fecha'];
            insertNuevaFactura($cliente_nm,$fecha);
        }
        else if(isSaveButtonTriggeredtoUpdateFactura()){
            $cliente_nm = $_POST['cliente_nm'];
            $fecha = $_POST['fecha'];
            $factura_id = $_POST['factura_id'];
            updateFechaAndClienteFromFactura($fecha,$cliente_nm,$factura_id);
        }
    }

    function isSaveButtonTriggeredWithValues(){
       return isset($_POST['fecha']) && isset($_POST['cliente_nm']) && isset($_POST['guardar-factura']) && !isset($_POST['factura_id']);
    }

    function isSaveButtonTriggeredtoUpdateFactura(){
        return isset($_POST['fecha']) && isset($_POST['cliente_nm']) && isset($_POST['guardar-factura']) && isset($_POST['factura_id']);
    }

    function isSaveProductButtonTriggered(){
        return isset($_POST['cantidad']) && isset($_POST['descripcion']) && isset($_POST['valor_unitario']) && isset($_POST['guardar-producto']) && isset($_POST['factura_id']);
    }

    function isOpenFacturaButtonTriggered(){
        return isset($_POST['abrir-factura']) || isset($_POST['guardar-producto']);
    }

    function isDeleteFacturaButtonTriggered(){
        return isset($_POST['remover-factura']);
    }

    function isDeleteProductoButtonTriggered(){
        return isset($_POST['remover-producto']);
    }

    function XMLFileExists(){
        global $json_file_path;
        return file_exists($json_file_path);
    }

    function createXMLFileIfExists(){
        if(!XMLFileExists()){
            global $json_file_path;
            $file = fopen($json_file_path, "w+");

            $data = array();
            $data['facturas'] = array();
            $data['productos'] = array();
            $json = json_encode($data);
            fwrite($file, $json);
            fclose($file);
        }
    }

    function insertNuevoProducto(){
        global $json_file_path;
        $factura_id = $_POST['factura_id'];
        $cantidad = $_POST['cantidad'];
        $valor_unitario = $_POST['valor_unitario'];
        $descripcion = $_POST['descripcion'];
        $subtotal = $valor_unitario * $cantidad;

        $last_id = getLastProductIdInserted() + 1;
        
        //Get previous data from json data
        $stringData = file_get_contents($json_file_path);
        $data = json_decode($stringData, true);
        
        //Insert new product in the end products array
        array_push($data['productos'], 
                    array("producto_id"=>$last_id,"factura_id"=>$factura_id,"cantidad"=>$cantidad,"descripcion"=>$descripcion,
                         "valor_unitario"=>$valor_unitario,"subtotal"=>$subtotal));
        $json = json_encode($data);

        //Save the changes into the file
        $file = fopen($json_file_path, "w+");
        fwrite($file, $json);
        fclose($file);
        updateFactura($factura_id,$subtotal);
    }

    //It work in both sides when a product is added and when a product is delete we just have to pass the subtotal of the product
    //If is a product removal the sutotal will be negative
    function updateFactura($factura_id,$subtotal){
        global $json_file_path;
        $facturaResult = getFacturaById($factura_id);
        
        $factura_id = (int)$facturaResult['factura_id'];
        $impuesto = $facturaResult['impuesto'];
        $total = $facturaResult['total'];

        $subimpuesto = ($subtotal * 0.13);
        $impuesto = $impuesto + $subimpuesto;
        $total = $total + ($subtotal + $subimpuesto);

        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
    
        foreach($json['facturas'] as &$row){
            if($row['factura_id'] == $factura_id){
                $row['impuesto'] = $impuesto;
                $row['total'] = $total;
            }
        }

        $dataUpdated = json_encode($json);

        //Save the changes into the file
        $file = fopen($json_file_path, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }

    function updateFechaAndClienteFromFactura($newfecha,$newcliente_nm,$factura_id){
        global $json_file_path;
        $facturaResult = getFacturaById($factura_id);
        
        $factura_id = (int)$facturaResult['factura_id'];

        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
    
        foreach($json['facturas'] as &$row){
            if($row['factura_id'] == $factura_id){
                $row['fecha'] = $newfecha;
                $row['cliente_nm'] = $newcliente_nm;
            }
        }

        $dataUpdated = json_encode($json);

        //Save the changes into the file
        $file = fopen($json_file_path, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }

    function getLastProductIdInserted(){
        global $json_file_path;
        $last_id = 0;
        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
        if(empty($json['productos'])){
            return $last_id;
        }
        else{
            foreach($json['productos'] as $row){
                $last_id = (int)$row['producto_id'];
            }
            return $last_id;
        }
    }

    function getLastFacturaIdInserted(){
        global $json_file_path;
        $last_id = 0;
        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
        if(empty($json['facturas'])){
            return $last_id;
        }
        else{
            foreach($json['facturas'] as $row){
                $last_id = (int)$row['factura_id'];
            }
            return $last_id;
        }
    }

    function insertNuevaFactura($cliente_nm, $fecha){
        global $json_file_path;
        $impuesto = 0.00;
        $total = 0.00;
        $last_id = getLastFacturaIdInserted() + 1;
        
        //Get previous data from json data
        $stringData = file_get_contents($json_file_path);
        $data = json_decode($stringData, true);
        
        //Insert new factura in the end facturas array
        array_push($data['facturas'], array("factura_id"=>$last_id,"cliente_nm"=>$cliente_nm,"fecha"=>$fecha,"impuesto"=>$impuesto,"total"=>$total));
        $json = json_encode($data);

        //Save the changes into the file
        $file = fopen($json_file_path, "w+");
        fwrite($file, $json);
        fclose($file);
    }

    function getFacturaById($factura_id){
        global $json_file_path;
        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
        if(!empty($json['facturas'])){
            foreach($json['facturas'] as $row) {
                if($row['factura_id'] == $factura_id){
                    return $row;
                }
            }
            return array();
        }
    }

    function getProductsByFacturaId($factura_id){
        global $json_file_path;
        $products = array();
        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            foreach($json['productos'] as $row) {
                if($row['factura_id'] == $factura_id){
                    array_push($products,$row);
                }
            }
            return $products;
        }
    }

    function getProductById($producto_id){
        global $json_file_path;
        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            foreach($json['productos'] as $row) {
                if($row['producto_id'] == $producto_id){
                    return $row;
                }
            }
            return array();
        }
    }

    function deleteAllProductsByFacturaId($factura_id){
        global $json_file_path;
        
        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            $i=0;
            foreach($json['productos'] as $row) {
                if($row['factura_id'] == $factura_id){
                    unset($json['productos'][$i]);
                }
                $i++;
            }
        }

        $json['productos'] = array_values($json['productos']);
        $dataUpdated = json_encode($json);

        //Save the changes into the file
        $file = fopen($json_file_path, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }

    function deleteFacturaById($factura_id){
        global $json_file_path;

        deleteAllProductsByFacturaId($factura_id);

        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
        if(!empty($json['facturas'])){
            $i=0;
            foreach($json['facturas'] as $row) {
                if($row['factura_id'] == $factura_id){
                    unset($json['facturas'][$i]);
                }
                $i++;
            }
        }

        $json['facturas'] = array_values($json['facturas']);
        $dataUpdated = json_encode($json);

        //Save the changes into the file
        $file = fopen($json_file_path, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }

    function deleteProductoById($producto_id){
        global $json_file_path;
        
        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            $i=0;
            foreach($json['productos'] as $row) {
                if($row['producto_id'] == $producto_id){
                    $subtotal = $row['subtotal'] * -1;
                    $factura_id = $row['factura_id'];
                    unset($json['productos'][$i]);
                }
                $i++;
            }
        }

        $json['productos'] = array_values($json['productos']);
        $dataUpdated = json_encode($json);

        //Save the changes into the file
        $file = fopen($json_file_path, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);

        updateFactura($factura_id,$subtotal);
    }

    function fillAllReceiptsTable(){
        global $json_file_path;
        
        $data = file_get_contents($json_file_path);
        $json = json_decode($data, true);
        if(!empty($json['facturas'])){
            foreach($json['facturas'] as $row) {
                $factura_id = $row['factura_id'];
                $cliente_nm = $row['cliente_nm'];
                echo "<tr>";
                echo "<td>$factura_id</td>";
                echo "<td>$cliente_nm</td>";
                echo "<td><form method='POST' action='index.php'><input type='hidden' name='remover-factura' value='$factura_id'><input type='submit' value='Eliminar' class='btn btn-danger'></form>";
                echo "<form method='POST' action='index.php'><input type='hidden' name='abrir-factura' value='$factura_id'><input type='submit' value='Abrir' class='btn btn-info'></form></td>";
                echo "</tr>";
            }
        } 
    }

    function fillFacturaSection(){
        if(isOpenFacturaButtonTriggered()){
            if(isset($_POST['abrir-factura'])){
                $factura_id = $_POST['abrir-factura'];
            }
            else if(isSaveProductButtonTriggered()){
                $factura_id = $_POST['factura_id'];
                insertNuevoProducto();
            }
            
            $result = getFacturaById($factura_id);
            $factura_id = $result['factura_id'];
            $cliente_nm = $result['cliente_nm'];
            $fecha = $result['fecha'];
            $impuesto = $result['impuesto'];
            $total = $result['total'];
            echo "<form method='POST' action='index.php'>";
            echo "<div class='form-group'>";
            echo "<label for='factura_id'>Numero Factura</label>";
            echo "<input type='text' id='factura_id' name='factura_id' value='$factura_id' class='form-control' disabled>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='fecha'>Fecha</label>";
            echo "<input type='datetime-local' id='fecha' name='fecha' value='$fecha' class='form-control'>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='cliente_nm'>Nombre Cliente</label>";
            echo "<input type='text' id='cliente_nm' name='cliente_nm' value='$cliente_nm' class='form-control'>";
            echo "</div>";
            echo "<div class='table-responsive'>";
            echo "<table class='table'><thead class='thead-dark'><tr>";
            echo "<th scope='col'>Cantidad</th><th scope='col'>Descripcion</th><th scope='col'>Valor Unitario</th><th scope='col'>Subtotal</th><th scope='col'>Action</th>";
            echo "</tr></thead>";
            echo "<tbody>";
            $productsresult = getProductsByFacturaId($factura_id);
            if(!empty($productsresult)){
                foreach($productsresult as $row){
                    $producto_id = $row['producto_id'];
                    $cantidad = $row['cantidad'];
                    $descripcion = $row['descripcion'];
                    $valor_unitario = $row['valor_unitario'];
                    $subtotal = $row['subtotal'];
                    echo "<input type='hidden' name='producto_id' value='$producto_id'>";
                    echo "<tr><td>$cantidad</td><td>$descripcion</td><td>$valor_unitario</td><td>$subtotal</td>";
                    echo "<td><input type='submit' name='remover-producto' value='Remover' class='btn btn-danger'></td></tr>";
                }
            }
            echo "<tr>";
            echo "<input type='hidden' name='factura_id' value='$factura_id'>";
            echo "<td><input type='number' id='cantidad' name='cantidad'></td>";
            echo "<td><input type='text' id='descripcion' name='descripcion'></td>";
            echo "<td><input type='number' id='valor_unitario' name='valor_unitario' step='0.01'></td>";
            echo "<td><input type='number' id='subtotal' name='subtotal' step='0.01' disabled></td>";
            echo "<td><input type='submit' name='guardar-producto' value='Guardar Producto' class='btn btn-primary'></td>";
            echo "</tr>";
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
            echo "<div class='form-row'>";
            echo "<div class='form-group col-md-6'>";
            echo "<label for='impuesto'>Impuesto</label>";
            echo "<input type='number' id='impuesto' name='impuesto' step='0.01' value='$impuesto' class='form-control' disabled>";
            echo "</div>";
            echo "<div class='form-group col-md-6'>";
            echo "<label for='total'>Total</label>";
            echo "<input type='number' id='total' name='total' step='0.01' value='$total' class='form-control' disabled>";
            echo "</div>";
            echo "<input type='submit' name='guardar-factura' value='Guardar' class='btn btn-primary'>";
            echo "</div>";
            echo "</form>";
        }
        else{
            echo "<form method='POST' action='index.php'>";
            echo "<div class='form-group'>";
            echo "<label for='factura_id'>Numero Factura</label>";
            echo "<input type='text' id='factura_id' name='factura_id' class='form-control' disabled>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='fecha'>Fecha</label>";
            echo "<input type='datetime-local' id='fecha' name='fecha' class='form-control'>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='cliente_nm'>Nombre Cliente</label>";
            echo "<input type='text' id='cliente_nm' name='cliente_nm' class='form-control'>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='impuesto'>Impuesto</label>";
            echo "<input type='number' id='impuesto' name='impuesto' step='0.01' value='0.00' class='form-control' disabled>";
            echo "</div>";
            echo "<div class='form-group'>";
            echo "<label for='total'>Total</label>";
            echo "<input type='number' id='total' name='total' step='0.01' value='0.00' class='form-control' disabled>";
            echo "</div>";
            echo "<input type='submit' name='guardar-factura' value='Guardar' class='btn btn-primary'>";
            echo "</form>";
        }
        
    }
?>
<!DOCTYPE html>
<html>
    <head>   
        <meta charset="utf-8">
        <title>Tarea 5</title>
        <link rel="stylesheet" href="styles/style.css">
        <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    </head>
    <body>
        <header></header>
        <main class="container">
            <div class="row">
                <section class="col-md-4" id="receipts">
                    <p>Facturas</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col">Numero</th>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    fillAllReceiptsTable();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <section class="col-md-8" id="factura">
                <p>Factura</p>
                    <?php 
                        fillFacturaSection();
                    ?>
                </section>
            </div>
        </main>
        <footer></footer>
    </body>
</html>