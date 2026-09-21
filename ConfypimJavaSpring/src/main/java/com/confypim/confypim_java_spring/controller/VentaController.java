
package com.confypim.confypim_java_spring.controller;

import com.confypim.confypim_java_spring.model.DetalleVenta;
import com.confypim.confypim_java_spring.model.Venta;
import com.confypim.confypim_java_spring.service.VentaService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;   

@RestController
@RequestMapping("/api/ventas")
public class VentaController {

    private final VentaService ventaService;

    public VentaController(VentaService ventaService) {
        this.ventaService = ventaService;
    }

    // ==========================================================
    // LISTAR VENTAS
    // ==========================================================

    @GetMapping
    public List<Venta> listarVentas() {
        return ventaService.listarVentasVisibles();
    }

    // ==========================================================
    // OBTENER VENTA
    // ==========================================================

    @GetMapping("/{idVenta}")
    public ResponseEntity<?> obtenerVenta(
            @PathVariable Integer idVenta
    ) {

        Venta venta = ventaService.obtenerVentaPorId(idVenta);

        if (venta == null) {
            return ResponseEntity.notFound().build();
        }

        List<DetalleVenta> detalles =
                ventaService.obtenerDetallesPorVenta(idVenta);

        return ResponseEntity.ok(
                Map.of(
                        "venta", venta,
                        "detalles", detalles
                )
        );
    }

    // ==========================================================
    // CREAR VENTA
    // ==========================================================

    @PostMapping
    public ResponseEntity<?> crearVenta(
            @RequestBody SolicitudVenta solicitud
    ) {

        Venta venta = solicitud.venta();
        List<DetalleVenta> detalles = solicitud.detalles();

        Integer idVenta = ventaService.crearVenta(
                venta,
                detalles
        );

        return ResponseEntity.ok(
                Map.of(
                        "mensaje", "Venta creada correctamente.",
                        "idVenta", idVenta
                )
        );
    }

    // ==========================================================
    // ACTUALIZAR VENTA
    // ==========================================================

    @PutMapping("/{idVenta}")
    public ResponseEntity<?> actualizarVenta(
            @PathVariable Integer idVenta,
            @RequestBody SolicitudVenta solicitud
    ) {

        ventaService.actualizarVenta(
                idVenta,
                solicitud.venta(),
                solicitud.detalles()
        );

        return ResponseEntity.ok(
                Map.of(
                        "mensaje", "Venta actualizada correctamente."
                )
        );
    }

    // ==========================================================
    // PAGAR VENTA
    // ==========================================================

    @PatchMapping("/{idVenta}/pagar")
    public ResponseEntity<?> pagarVenta(
            @PathVariable Integer idVenta
    ) {

        ventaService.pagarVenta(idVenta);

        return ResponseEntity.ok(
                Map.of(
                        "mensaje", "Venta pagada correctamente."
                )
        );
    }

    // ==========================================================
    // CANCELAR VENTA
    // ==========================================================

    @PatchMapping("/{idVenta}/cancelar")
    public ResponseEntity<?> cancelarVenta(
            @PathVariable Integer idVenta
    ) {

        ventaService.cancelarVenta(idVenta);

        return ResponseEntity.ok(
                Map.of(
                        "mensaje", "Venta cancelada correctamente."
                )
        );
    }

    // ==========================================================
    // DTO PARA LAS SOLICITUDES
    // ==========================================================

    public record SolicitudVenta(
            Venta venta,
            List<DetalleVenta> detalles
    ) {
    }
}