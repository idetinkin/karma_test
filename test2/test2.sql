CREATE TABLE Users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       username VARCHAR(50) NOT NULL
);

CREATE TABLE Orders (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT,
                        amount DECIMAL(10, 2),
                        payed BOOLEAN,
                        FOREIGN KEY (user_id) REFERENCES Users(id)
);

CREATE TABLE Payments (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          order_id INT,
                          amount DECIMAL(10, 2),
                          pay_system VARCHAR(50),
                          status ENUM('success', 'failed') NOT NULL,
                          FOREIGN KEY (order_id) REFERENCES Orders(id)
);

INSERT INTO Users (id, username) VALUES
(1, 'username1'),
(2, 'username2'),
(3, 'username3'),
(4, 'username4'),
(5, 'username5'),
(6, 'username6');

INSERT INTO Orders (id, user_id, amount, payed) VALUES
-- username1: paid/not paid = 3
(1, 1, 50.00, FALSE),
(2, 1, 100.00, TRUE),
(3, 1, 75.00, TRUE),
(4, 1, 150.00, TRUE),

-- username2: paid/not pid = 1
(5, 2, 200.00, FALSE),
(6, 2, 150.00, TRUE),

-- username3: paid/not paid = 2
(7, 3, 50.00, FALSE),
(8, 3, 100.00, TRUE),
(9, 3, 100.00, TRUE),

-- username4: paid/not paid = 0.67
(10, 4, 60.00, FALSE),
(11, 4, 120.00, FALSE),
(12, 4, 110.00, FALSE),
(13, 4, 110.00, TRUE),
(14, 4, 110.00, TRUE),

-- username5: paid/not paid = 1.5
(15, 5, 60.00, FALSE),
(16, 5, 120.00, FALSE),
(17, 5, 130.00, TRUE),
(18, 5, 60.00, TRUE),
(19, 5, 440.00, TRUE),

-- username6: paid/not paid = 2.5
(20, 6, 60.00, FALSE),
(21, 6, 120.00, FALSE),
(22, 6, 320.00, TRUE),
(23, 6, 90.00, TRUE),
(24, 6, 140.00, TRUE),
(25, 6, 50.00, TRUE),
(26, 6, 370.00, TRUE);

-- only username1 and username6 has paid/not paid > 2

INSERT INTO Payments (order_id, amount, pay_system, status) VALUES
-- username1: 20% failed
(1, 100.00, 'Bank Transfer', 'failed'),
(2, 100.00, 'PayPal', 'success'),
(3, 100.00, 'Credit Card', 'success'),
(4, 100.00, 'PayPal', 'success'),
(4, 100.00, 'Credit Card', 'success'),

-- username2: 0% failed
(5, 100.00, 'Bank Transfer', 'success'),
(5, 100.00, 'PayPal', 'success'),
(5, 100.00, 'Credit Card', 'success'),
(6, 100.00, 'PayPal', 'success'),
(6, 100.00, 'Credit Card', 'success'),

-- username3: 100% failed
(7, 100.00, 'Bank Transfer', 'failed'),
(7, 100.00, 'PayPal', 'failed'),
(8, 100.00, 'Credit Card', 'failed'),
(8, 100.00, 'PayPal', 'failed'),
(9, 100.00, 'Credit Card', 'failed'),

-- username4: 50% failed
(10, 100.00, 'Bank Transfer', 'failed'),
(11, 100.00, 'PayPal', 'failed'),
(12, 100.00, 'Credit Card', 'success'),
(13, 100.00, 'PayPal', 'success'),

-- username5 - no payments

-- username6: 14% failed
(20, 100.00, 'Bank Transfer', 'failed'),
(21, 100.00, 'PayPal', 'success'),
(22, 100.00, 'Credit Card', 'success'),
(23, 100.00, 'PayPal', 'success'),
(24, 100.00, 'Bank Transfer', 'success'),
(25, 100.00, 'PayPal', 'success'),
(26, 100.00, 'Credit Card', 'success');

-- only users username2 and username6 has less than 15% failed payments
-- username5 hos no payments - sounds reasonable to assume that we don't want this user to be in the final result

-- the final result should display only username6
SELECT u.id,
       u.username
FROM Users u
     LEFT JOIN Orders o ON u.id = o.user_id
     LEFT JOIN Payments p ON o.id = p.order_id
GROUP BY u.id, u.username
HAVING SUM(CASE WHEN o.payed = 1 THEN 1 ELSE 0 END) > 2 * SUM(CASE WHEN o.payed = 0 THEN 1 ELSE 0 END)
   AND SUM(CASE WHEN p.status = 'failed' THEN 1 ELSE 0 END) / COUNT(p.id) < 0.15;