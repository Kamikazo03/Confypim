package com.confypim.confypim_java_spring.controller;

// Importación de la anotación que permite crear un controlador MVC.
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;


/**
 * Controlador encargado de mostrar la interfaz visual
 * correspondiente al módulo de ventas de Confypim.
 *
 * Este controlador utiliza Thymeleaf para devolver
 * una vista HTML ubicada dentro de resources/templates.
 *
 * Las operaciones CRUD se realizan mediante JavaScript
 * utilizando los endpoints REST del VentaController.
 */
@Controller

// Ruta base utilizada para acceder a la pantalla de ventas.
@RequestMapping("/ventas")
public class VentaViewController {

    /**
     * Muestra la página principal del módulo de ventas.
     *
     * Cuando el usuario visita:
     *
     * http://localhost:8080/ventas
     *
     * Spring Boot buscará el archivo:
     *
     * templates/ventas/ventas.html
     *
     * El prefijo "redirect" no es necesario porque
     * estamos devolviendo directamente el nombre de la vista.
     */
    @GetMapping
    public String mostrarVistaVentas() {

        // Retorna la vista ventas.html ubicada
        // dentro de la carpeta templates/ventas.
        return "ventas/ventas";
    }
}