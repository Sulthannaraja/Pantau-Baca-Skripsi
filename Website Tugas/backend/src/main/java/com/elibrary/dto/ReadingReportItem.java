package com.elibrary.dto;

import java.time.LocalDateTime;

public record ReadingReportItem(
        Long logId,
        Long userId,
        String userName,
        Long bookId,
        String bookTitle,
        Integer duration,
        Integer progress,
        Integer lastPage,
        LocalDateTime updatedAt
) {}
