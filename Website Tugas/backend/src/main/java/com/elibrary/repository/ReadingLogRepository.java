package com.elibrary.repository;

import com.elibrary.model.ReadingLog;
import com.elibrary.model.User;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface ReadingLogRepository extends JpaRepository<ReadingLog, Long> {
    List<ReadingLog> findByUserId(Long userId);
    List<ReadingLog> findByUserIdOrderByUpdatedAtDesc(Long userId);
    List<ReadingLog> findByUserOrderByUpdatedAtDesc(User user);
    Optional<ReadingLog> findByUserIdAndBookId(Long userId, Long bookId);
}
