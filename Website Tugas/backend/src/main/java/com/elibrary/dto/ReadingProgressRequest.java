package com.elibrary.dto;

import jakarta.validation.constraints.Max;
import jakarta.validation.constraints.Min;
import jakarta.validation.constraints.NotNull;

public record ReadingProgressRequest(
        @NotNull Long bookId,
        @NotNull @Min(0) Integer duration,
        @NotNull @Min(0) @Max(100) Integer progress,
        @NotNull @Min(1) Integer lastPage
) {}
