package com.confypim.confypim_java_spring.service;

import java.util.List;

import org.springframework.stereotype.Service;

import com.confypim.confypim_java_spring.model.Producto;
import com.confypim.confypim_java_spring.repository.ProductoRepository;

@Service
public class ProductoService {

    private final ProductoRepository productoRepository;

    public ProductoService(ProductoRepository productoRepository) {
        this.productoRepository = productoRepository;
    }

    public List<Producto> listarProductosActivos() {
        return productoRepository.listarProductosActivos();
    }
}