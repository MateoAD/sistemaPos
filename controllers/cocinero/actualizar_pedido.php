<?php
session_start();
require_once '../../controllers/db.php';

header('Content-Type: application/json');

class ActualizarPedidoController {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    public function marcarEnPreparacion($pedido_id) {
        try {
            $this->conn->begin_transaction();
            
            $query = "UPDATE pedidos SET 
                     estado = 'en_preparacion',
                     hora_inicio_preparacion = NOW()
                     WHERE id = ? AND estado = 'pendiente'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $pedido_id);
            $stmt->execute();
            
            if($stmt->affected_rows > 0) {
                $this->conn->commit();
                return ['success' => true, 'message' => 'Pedido marcado como en preparación'];
            } else {
                $this->conn->rollback();
                return ['success' => false, 'error' => 'No se pudo actualizar el pedido'];
            }
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function marcarCompletado($pedido_id) {
        try {
            $this->conn->begin_transaction();
            
            $query = "UPDATE pedidos SET 
                     estado = 'completado',
                     hora_completado = NOW()
                     WHERE id = ? AND estado = 'en_preparacion'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $pedido_id);
            $stmt->execute();
            
            if($stmt->affected_rows > 0) {
                $this->conn->commit();
                return ['success' => true, 'message' => 'Pedido marcado como completado'];
            } else {
                $this->conn->rollback();
                return ['success' => false, 'error' => 'No se pudo actualizar el pedido'];
            }
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function actualizarEstadoPedido($pedido_id, $nuevo_estado) {
        try {
            $estados_validos = ['pendiente', 'en_preparacion', 'completado'];
            
            if(!in_array($nuevo_estado, $estados_validos)) {
                throw new Exception("Estado no válido");
            }
            
            $query = "UPDATE pedidos SET estado = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $nuevo_estado, $pedido_id);
            $stmt->execute();
            
            if($stmt->affected_rows > 0) {
                return ['success' => true, 'message' => 'Estado actualizado correctamente'];
            } else {
                return ['success' => false, 'error' => 'No se pudo actualizar el estado'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Manejo de peticiones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $controller = new ActualizarPedidoController();
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'marcarEnPreparacion':
                if(isset($data['pedido_id'])) {
                    $result = $controller->marcarEnPreparacion($data['pedido_id']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
                }
                break;
                
            case 'marcarCompletado':
                if(isset($data['pedido_id'])) {
                    $result = $controller->marcarCompletado($data['pedido_id']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
                }
                break;
                
            case 'actualizarEstado':
                if(isset($data['pedido_id']) && isset($data['estado'])) {
                    $result = $controller->actualizarEstadoPedido($data['pedido_id'], $data['estado']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                }
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