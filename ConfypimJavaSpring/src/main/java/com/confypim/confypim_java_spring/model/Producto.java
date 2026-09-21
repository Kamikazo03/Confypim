package com.confypim.confypim_java_spring.model;

import java.math.BigDecimal;

public class Producto {

    private Integer idProducto;
    private String nombre;
    private BigDecimal precioUnitario;
    private Integer stockDisponible;
    private Boolean activo;

    public Producto() {
    }

    public Producto(
            Integer idProducto,
            String nombre,
            BigDecimal precioUnitario,
            Integer stockDisponible,
            Boolean activo
    ) {
        this.idProducto = idProducto;
        this.nombre = nombre;
        this.precioUnitario = precioUnitario;
        this.stockDisponible = stockDisponible;
        this.activo = activo;
    }

    public Integer getIdProducto() {
        return idProducto;
    }

    public void setIdProducto(Integer idProducto) {
        this.idProducto = idProducto;
    }

    public String getNombre() {
        return nombre;
    }

    public void setNombre(String nombre) {
        this.nombre = nombre;
    }

    public BigDecimal getPrecioUnitario() {
        return precioUnitario;
    }

    public void setPrecioUnitario(BigDecimal precioUnitario) {
        this.precioUnitario = precioUnitario;
    }

    public Integer getStockDisponible() {
        return stockDisponible;
    }

    public void setStockDisponible(Integer stockDisponible) {
        this.stockDisponible = stockDisponible;
    }

    public Boolean getActivo() {
        return activo;
    }

    public void setActivo(Boolean activo) {
        this.activo = activo;
    }
}