package com.elibrary.dto;

import java.time.LocalDateTime;

public record ReadingSessionResponse(
        Long logId,
        Long userId,
        Long bookId,
        Integer duration,
        Integer progress,
        Integer lastPage,
        LocalDateTime startedAt,
        LocalDateTime updatedAt
) {}
