package com.elibrary.controller;

import com.elibrary.dto.ReadingProgressRequest;
import com.elibrary.dto.ReadingReportItem;
import com.elibrary.dto.ReadingSessionResponse;
import com.elibrary.dto.ReadingStartRequest;
import com.elibrary.model.ReadingLog;
import com.elibrary.model.Role;
import com.elibrary.security.UserPrincipal;
import com.elibrary.service.ReadingService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/reading")
@RequiredArgsConstructor
public class ReadingController {
    private final ReadingService readingService;

    @PostMapping("/start")
    public ReadingSessionResponse start(@AuthenticationPrincipal UserPrincipal user,
                                        @Valid @RequestBody ReadingStartRequest request) {
        requireStudent(user);
        return toSessionResponse(readingService.start(user.getId(), request));
    }

    @PostMapping("/progress")
    public ReadingSessionResponse progress(@AuthenticationPrincipal UserPrincipal user,
                                           @Valid @RequestBody ReadingProgressRequest request) {
        requireStudent(user);
        return toSessionResponse(readingService.updateProgress(user.getId(), request));
    }

    @GetMapping("/report")
    public List<ReadingReportItem> report(@AuthenticationPrincipal UserPrincipal user,
                                          @RequestParam(required = false) Long userId) {
        if (user == null) {
            throw new SecurityException("Unauthorized");
        }

        if (user.getRole() == Role.GURU || user.getRole() == Role.ADMIN) {
            if (userId != null) {
                return readingService.getReportByUser(userId);
            }
            return readingService.getReport();
        }

        return readingService.getReportByUser(user.getId());
    }

    private void requireStudent(UserPrincipal user) {
        if (user == null || user.getRole() != Role.SISWA) {
            throw new SecurityException("Hanya siswa yang boleh mengakses");
        }
    }

    private ReadingSessionResponse toSessionResponse(ReadingLog log) {
        return new ReadingSessionResponse(
                log.getId(),
                log.getUser().getId(),
                log.getBook().getId(),
                log.getDuration(),
                log.getProgress(),
                log.getLastPage(),
                log.getStartedAt(),
                log.getUpdatedAt()
        );
    }
}
