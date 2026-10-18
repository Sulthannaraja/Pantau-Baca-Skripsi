package com.elibrary.dto;

import jakarta.validation.constraints.NotNull;

public record ReadingStartRequest(
        @NotNull Long bookId,
        @NotNull Integer currentPage
) {}
