<?php
session_start();
require_once '../../controllers/db.php';

header('Content-Type: application/json');

class CocineroController {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function obtenerPendientes() {
        try {
            $query = "SELECT p.id, p.mesa_id, p.nombre_cliente, p.fecha_hora, m.numero_mesa,
                             TIMESTAMPDIFF(MINUTE, p.fecha_hora, NOW()) as tiempo_transcurrido
                      FROM pedidos p 
                      JOIN mesas m ON p.mesa_id = m.id 
                      WHERE p.estado = 'pendiente' 
                      ORDER BY p.fecha_hora ASC";
            
            $result = $this->conn->query($query);
            $pedidos = [];
            
            while($row = $result->fetch_assoc()) {
                $row['detalles'] = $this->obtenerDetallesPedido($row['id']);
                $row['tiempo_transcurrido'] = $this->formatearTiempo($row['tiempo_transcurrido']);
                $pedidos[] = $row;
            }
            
            return ['success' => true, 'pedidos' => $pedidos];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function obtenerEnPreparacion() {
        try {
            $query = "SELECT p.id, p.mesa_id, p.nombre_cliente, p.fecha_hora, p.hora_inicio_preparacion, m.numero_mesa,
                             TIMESTAMPDIFF(MINUTE, p.hora_inicio_preparacion, NOW()) as tiempo_preparacion
                      FROM pedidos p 
                      JOIN mesas m ON p.mesa_id = m.id 
                      WHERE p.estado = 'en_preparacion' 
                      ORDER BY p.hora_inicio_preparacion ASC";
            
            $result = $this->conn->query($query);
            $pedidos = [];
            
            while($row = $result->fetch_assoc()) {
                $row['detalles'] = $this->obtenerDetallesPedido($row['id']);
                $row['tiempo_transcurrido'] = $this->formatearTiempo($row['tiempo_preparacion']);
                $pedidos[] = $row;
            }
            
            return ['success' => true, 'pedidos' => $pedidos];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function obtenerCompletadosHoy() {
        try {
            $query = "SELECT p.id, p.mesa_id, p.nombre_cliente, p.fecha_hora, p.hora_completado, m.numero_mesa,
                             TIMESTAMPDIFF(MINUTE, p.fecha_hora, p.hora_completado) as tiempo_total
                      FROM pedidos p 
                      JOIN mesas m ON p.mesa_id = m.id 
                      WHERE p.estado = 'completado' 
                      AND DATE(p.hora_completado) = CURDATE()
                      ORDER BY p.hora_completado DESC
                      LIMIT 20";
            
            $result = $this->conn->query($query);
            $pedidos = [];
            
            while($row = $result->fetch_assoc()) {
                $row['detalles'] = $this->obtenerDetallesPedido($row['id']);
                $row['tiempo_transcurrido'] = $this->formatearTiempo($row['tiempo_total']);
                $pedidos[] = $row;
            }
            
            return ['success' => true, 'pedidos' => $pedidos];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function obtenerDetallesPedido($pedido_id) {
        $query = "SELECT dp.*, pl.nombre as nombre_plato, pl.categoria 
                  FROM detalle_pedido dp 
                  JOIN platos pl ON dp.plato_id = pl.id 
                  WHERE dp.pedido_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $pedido_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $detalles = [];
        while($row = $result->fetch_assoc()) {
            $detalles[] = $row;
        }
        
        return $detalles;
    }
    
    public function obtenerDetallesCompletos($pedido_id) {
        try {
            $query = "SELECT p.*, m.numero_mesa 
                      FROM pedidos p 
                      JOIN mesas m ON p.mesa_id = m.id 
                      WHERE p.id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $pedido_id);
            $stmt->execute();
            $pedido = $stmt->get_result()->fetch_assoc();
            
            if(!$pedido) {
                throw new Exception("Pedido no encontrado");
            }
            
            $pedido['detalles'] = $this->obtenerDetallesPedido($pedido_id);
            
            $html = '<div class="row">';
            $html .= '<div class="col-md-6">';
            $html .= '<p><strong>Mesa:</strong> ' . $pedido['numero_mesa'] . '</p>';
            $html .= '<p><strong>Cliente:</strong> ' . $pedido['nombre_cliente'] . '</p>';
            $html .= '<p><strong>Fecha:</strong> ' . date('d/m/Y H:i', strtotime($pedido['fecha_hora'])) . '</p>';
            $html .= '</div>';
            $html .= '<div class="col-md-6">';
            if($pedido['notas']) {
                $html .= '<p><strong>Notas:</strong> ' . htmlspecialchars($pedido['notas']) . '</p>';
            }
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr><th>Cantidad</th><th>Plato</th><th>Categoría</th></tr></thead>';
            $html .= '<tbody>';
            
            foreach($pedido['detalles'] as $detalle) {
                $html .= '<tr>';
                $html .= '<td>' . $detalle['cantidad'] . '</td>';
                $html .= '<td>' . htmlspecialchars($detalle['nombre_plato']) . '</td>';
                $html .= '<td>' . htmlspecialchars($detalle['categoria']) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            
            return ['success' => true, 'html' => $html];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function contarPedidosPendientes() {
        try {
            $query = "SELECT COUNT(*) as total FROM pedidos WHERE estado IN ('pendiente', 'en_preparacion')";
            $result = $this->conn->query($query);
            $total = $result->fetch_assoc()['total'];
            
            return ['success' => true, 'total' => $total];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function formatearTiempo($minutos) {
        if($minutos < 60) {
            return $minutos . ' min';
        } else {
            $horas = floor($minutos / 60);
            $min_restantes = $minutos % 60;
            return $horas . 'h ' . $min_restantes . 'min';
        }
    }
}

// Manejo de peticiones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $controller = new CocineroController();
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'obtenerPendientes':
                $result = $controller->obtenerPendientes();
                echo json_encode($result);
                break;
                
            case 'obtenerEnPreparacion':
                $result = $controller->obtenerEnPreparacion();
                echo json_encode($result);
                break;
                
            case 'obtenerCompletadosHoy':
                $result = $controller->obtenerCompletadosHoy();
                echo json_encode($result);
                break;
                
            case 'obtenerDetalles':
                if(isset($data['pedido_id'])) {
                    $result = $controller->obtenerDetallesCompletos($data['pedido_id']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
                }
                break;
                
            case 'contarPendientes':
                $result = $controller->contarPedidosPendientes();
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No se especificó acción']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}
?>