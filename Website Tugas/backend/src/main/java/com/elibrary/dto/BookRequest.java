package com.elibrary.dto;

import jakarta.validation.constraints.NotBlank;

public record BookRequest(
        @NotBlank String title,
        @NotBlank String author,
        @NotBlank String filePath,
        String cover,
        String category
) {}
