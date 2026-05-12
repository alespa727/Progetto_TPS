

INSERT INTO `manufacturers` (`name`, `url_name`) VALUES
('Intel', 'intel'),
('AMD', 'amd'),
('NVIDIA', 'nvidia'),
('Corsair', 'corsair'),
('Samsung', 'samsung');

INSERT INTO `categories` (`name`, `url_name`, `max_per_build`) VALUES
('CPU', 'cpu', 1),
('GPU', 'gpu', 2),
('RAM', 'ram', 4),
('Storage', 'storage', 6),
('Motherboard', 'motherboard', 1);

INSERT INTO `category_specs` (`category_id`, `spec_key`, `spec_label`, `unit`) VALUES
(1, 'cores', 'Numero di core', ''),
(1, 'frequency', 'Frequenza base', 'GHz'),
(2, 'vram', 'Memoria video', 'GB'),
(3, 'capacity', 'Capacità', 'GB'),
(4, 'read_speed', 'Velocità lettura', 'MB/s');

INSERT INTO `components` (`category_id`, `manufacturer_id`, `name`, `url_name`, `description`, `quantity`, `price`) VALUES
(1, 1, 'Intel Core i9-14900K', 'intel-core-i9-14900k', 'CPU desktop top di gamma Intel', 50, 58999),
(1, 2, 'AMD Ryzen 9 7950X', 'amd-ryzen-9-7950x', 'CPU AMD a 16 core per workstation', 30, 54999),
(2, 3, 'NVIDIA GeForce RTX 4090', 'nvidia-rtx-4090', 'GPU flagship di NVIDIA', 15, 189999),
(3, 4, 'Corsair Vengeance 32GB DDR5', 'corsair-vengeance-32gb-ddr5', 'Kit RAM DDR5 ad alte prestazioni', 100, 14999),
(4, 5, 'Samsung 990 Pro 2TB', 'samsung-990-pro-2tb', 'SSD NVMe PCIe 4.0 velocissimo', 80, 19999);

INSERT INTO `component_specs` (`component_id`, `spec_key`, `spec_value`, `unit`) VALUES
(1, 'cores', '24', ''),
(1, 'frequency', '3.2', 'GHz'),
(2, 'cores', '16', ''),
(3, 'vram', '24', 'GB'),
(5, 'read_speed', '7450', 'MB/s');

INSERT INTO `builds` (`user_id`, `name`, `description`, `status`, `is_public`, `total_price`) VALUES
(1, 'Gaming Beast 2024', 'Build gaming ultra high-end', 'published', 1, 289997.00),
(2, 'Workstation Pro', 'Postazione per rendering 3D', 'complete', 0, 209998.00),
(3, 'Budget Streamer', 'PC per streaming a budget moderato', 'draft', 0, 74998.00),
(4, 'Silent Office PC', 'PC silenzioso per ufficio', 'published', 1, 49998.00),
(5, 'AI Dev Machine', 'Macchina per sviluppo AI locale', 'draft', 0, 399996.00);

INSERT INTO `build_components` (`build_id`, `component_id`, `quantity`) VALUES
(1, 3, 1),
(1, 4, 2),
(2, 2, 1),
(2, 5, 2),
(3, 1, 1);

INSERT INTO `compatibility_rules` (`category_id`, `target_category_id`, `spec_key`, `target_spec_key`, `operator`, `required_value`) VALUES
(1, 3, 'memory_type', 'memory_type', NULL, '1'),
(3, 1, 'memory_type', 'memory_type', NULL, '1'),
(2, 5, 'pcie_version', 'pcie_version', '=', NULL),
(4, 5, 'interface', 'interface', '=', NULL),
(1, 2, 'tdp', 'tdp', '<', NULL);