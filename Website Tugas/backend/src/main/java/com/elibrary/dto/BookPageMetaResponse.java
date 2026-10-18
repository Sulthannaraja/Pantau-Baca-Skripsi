package com.elibrary.dto;

public record BookPageMetaResponse(
        Long bookId,
        Integer totalPages
) {}
