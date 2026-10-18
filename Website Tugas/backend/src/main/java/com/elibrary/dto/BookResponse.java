package com.elibrary.dto;

public record BookResponse(
        Long id,
        String title,
        String author,
        String cover,
        String category
) {}
