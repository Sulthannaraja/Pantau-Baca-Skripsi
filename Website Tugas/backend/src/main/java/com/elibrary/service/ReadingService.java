package com.elibrary.service;

import com.elibrary.dto.ReadingProgressRequest;
import com.elibrary.dto.ReadingReportItem;
import com.elibrary.dto.ReadingStartRequest;
import com.elibrary.model.Book;
import com.elibrary.model.ReadingLog;
import com.elibrary.model.User;
import com.elibrary.repository.BookRepository;
import com.elibrary.repository.ReadingLogRepository;
import com.elibrary.repository.UserRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;

import java.time.LocalDateTime;
import java.util.List;

@Service
@RequiredArgsConstructor
public class ReadingService {
    private final ReadingLogRepository readingLogRepository;
    private final UserRepository userRepository;
    private final BookRepository bookRepository;

    public ReadingLog start(Long userId, ReadingStartRequest request) {
        User user = userRepository.findById(userId).orElseThrow(() -> new IllegalArgumentException("User tidak ditemukan"));
        Book book = bookRepository.findById(request.bookId()).orElseThrow(() -> new IllegalArgumentException("Buku tidak ditemukan"));

        ReadingLog log = readingLogRepository.findByUserIdAndBookId(userId, request.bookId()).orElseGet(ReadingLog::new);
        log.setUser(user);
        log.setBook(book);
        log.setDuration(log.getDuration() == null ? 0 : log.getDuration());
        log.setProgress(log.getProgress() == null ? 0 : log.getProgress());
        log.setLastPage(request.currentPage());
        log.setStartedAt(log.getStartedAt() == null ? LocalDateTime.now() : log.getStartedAt());
        log.setUpdatedAt(LocalDateTime.now());

        return readingLogRepository.save(log);
    }

    public ReadingLog updateProgress(Long userId, ReadingProgressRequest request) {
        bookRepository.findById(request.bookId()).orElseThrow(() -> new IllegalArgumentException("Buku tidak ditemukan"));
        ReadingLog log = readingLogRepository.findByUserIdAndBookId(userId, request.bookId())
                .orElseThrow(() -> new IllegalArgumentException("Reading log belum dimulai"));

        log.setDuration(Math.max(log.getDuration(), request.duration()));
        log.setProgress(Math.max(log.getProgress(), request.progress()));
        log.setLastPage(Math.max(log.getLastPage(), request.lastPage()));
        log.setUpdatedAt(LocalDateTime.now());
        return readingLogRepository.save(log);
    }

    public List<ReadingReportItem> getReport() {
        return readingLogRepository.findAll().stream()
                .map(log -> new ReadingReportItem(
                        log.getId(),
                        log.getUser().getId(),
                        log.getUser().getName(),
                        log.getBook().getId(),
                        log.getBook().getTitle(),
                        log.getDuration(),
                        log.getProgress(),
                        log.getLastPage(),
                        log.getUpdatedAt()
                ))
                .toList();
    }

    public List<ReadingReportItem> getReportByUser(Long userId) {
        return readingLogRepository.findByUserIdOrderByUpdatedAtDesc(userId).stream()
                .map(log -> new ReadingReportItem(
                        log.getId(),
                        log.getUser().getId(),
                        log.getUser().getName(),
                        log.getBook().getId(),
                        log.getBook().getTitle(),
                        log.getDuration(),
                        log.getProgress(),
                        log.getLastPage(),
                        log.getUpdatedAt()
                ))
                .toList();
    }
}
