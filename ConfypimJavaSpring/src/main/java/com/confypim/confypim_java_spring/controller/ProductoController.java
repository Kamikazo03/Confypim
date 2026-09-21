package com.confypim.confypim_java_spring.controller;

import java.util.List;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

import com.confypim.confypim_java_spring.model.Producto;
import com.confypim.confypim_java_spring.service.ProductoService;

@RestController
public class ProductoController {

    private final ProductoService productoService;

    public ProductoController(ProductoService productoService) {
        this.productoService = productoService;
    }

    @GetMapping("/api/productos")
    public List<Producto> listarProductos() {
        return productoService.listarProductosActivos();
    }
}