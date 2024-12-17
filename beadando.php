<?php
#region DATA
const DATA = [
    'lastnames' => [
        'Major',
        'Riz',
        'Kard',
        'Pum',
        'Víz',
        'Kandisz',
        'Patta',
        'Para',
        'Pop',
        'Remek',
        'Ének',
        'Szalmon',
        'Ultra',
        'Dil',
        'Git',
        'Har',
        'Külö',
        'Harm',
        'Zsíros B.',
        'Virra',
        'Kasza',
        'Budipa',
        'Bekre',
        'Fejet',
        'Minden',
        'Bármi',
        'Lapos',
        'Bor',
        'Mikorka',
        'Szikla',
        'Fekete',
        'Rabsz',
        'Kalim',
        'Békés',
        'Szenyo',
    ],

    'firstnames' => [
        'men' => ['Ottó', 'Pál', 'Elek', 'Simon', 'Ödön', 'Kálmán', 'Áron', 'Elemér', 'Szilárd', 'Csaba'],
        'women' => ['Anna', 'Virág', 'Nóra', 'Zita', 'Ella', 'Viola', 'Emma', 'Áron', 'Mónika', 'Dóra', 'Blanka',
            'Piroska', 'Lenke', 'Mercédesz', 'Olga', 'Rita',]
    ],

    'classes' => [
        '11a', '11b', '11c', '12a', '12b', '12c',
    ],

    'subjects' => ['math', 'history', 'biology', 'chemistry', 'physics', 'informatics', 'alchemy', 'astrology', ],
];

#endregion

session_start();

#region head generation

function generate_head($title = 'Horlik Nimród Imre Szakközép Iskola') {
    return <<<HTML
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$title}</title>
        <link rel="stylesheet" href="style.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    </head>
HTML;
}
#endregion

#region diakok generalasa
function generate_student($class) {
    $gender = rand(0, 1) == 0 ? 'men' : 'women';
    $first_name = DATA['firstnames'][$gender][array_rand(DATA['firstnames'][$gender])];
    $last_name = DATA['lastnames'][array_rand(DATA['lastnames'])];
    $gender_text = $gender == 'men' ? 'fiú' : 'lány';
    $student = [
        'name' => "$last_name $first_name",  
        'gender' => $gender_text,
        'class' => $class,
        'grades' => [],
    ];

    foreach (DATA['subjects'] as $subject) {
        $num_grades = rand(0, 5);
        if ($num_grades > 0) {
            $student['grades'][$subject] = array_map(fn() => rand(1, 5), range(1, $num_grades));

        } else {
            $student['grades'][$subject] = ['nincs jegy'];
        }
    }
    return $student;
}

#endregion

#region osztalyok generalasa
function generate_class($class_name) {
    $num_students = rand(10, 15); 
    $students = [];
    for ($i = 0; $i < $num_students; $i++) {
        $students[] = generate_student($class_name);
    }

    return $students;
}

#endregion

#region session belerakasa
if (!isset($_SESSION['school'])) {
    $school = [];
    foreach (DATA['classes'] as $class_name) {
        $school[$class_name] = generate_class($class_name);

    }
    $_SESSION['school'] = $school;
} else {
    $school = $_SESSION['school'];
}
#endregion

#region ujra generalasa
if (isset($_POST['regenerate'])) {
    session_destroy();
    session_start();
    $school = [];
    foreach (DATA['classes'] as $class_name) {
        $school[$class_name] = generate_class($class_name);
    }
    $_SESSION['school'] = $school;
}
#endregion

#region oszzes osztaly kiirasa
function print_all_students($school) {
    echo "<div class='grid-container'>";
    foreach ($school as $class_name => $students) {
        usort($students, fn($a, $b) => strcmp($a['name'], $b['name']));
        echo "<div class='class-block'>";
        echo "<h2>Osztály: $class_name</h2>";
        foreach ($students as $student) {
            $student_average = calculate_student_average($student);
            echo "<p><strong>{$student['name']} ({$student['gender']}) - Átlag: {$student_average}</strong><br>";
            foreach ($student['grades'] as $subject => $grades) {
                $grades_text = implode(', ', $grades);
                echo "$subject: $grades_text<br>";
            }
            echo "</p>";
        }
        echo "</div>";
    }
    echo "</div>";
}
#endregion

#region egyes osztalyok kiirasa
function print_class_students($school, $class_name) {
    if (isset($school[$class_name])) {
        usort($school[$class_name], fn($a, $b) => strcmp($a['name'], $b['name']));
        echo "<h2>Osztály: $class_name</h2>";
        foreach ($school[$class_name] as $student) {
            $student_average = calculate_student_average($student);
            echo "<p><strong>{$student['name']} ({$student['gender']}) - Átlag: {$student_average}</strong><br>";
            foreach ($student['grades'] as $subject => $grades) {
                $grades_text = implode(', ', $grades);
                echo "$subject: $grades_text<br>";
            }
            echo "</p>";
        }
    }
}
#endregion

#region CSV export
function ensure_export_directory() {
    $export_dir = 'export';
    if (!file_exists($export_dir) || !is_dir($export_dir)) {
        mkdir($export_dir);
    }
    return $export_dir;
}

function generate_export_filename($class_name) {
    $timestamp = date('Y-m-d_Hi');
    return sprintf('%s-%s.csv', $class_name, $timestamp);
}

function export_class_to_csv($school, $class_name) {
    if (!isset($school[$class_name])) {
        return false;
    }

    $export_dir = ensure_export_directory();
    $filename = generate_export_filename($class_name);
    $filepath = $export_dir . '/' . $filename;

    $file = fopen($filepath, 'w');
    if (!$file) {
        return false;
    }

    $header = ['ID', 'Name', 'Firstname', 'Lastname', 'Gender', 'Subject', 'Marks'];
    fputcsv($file, $header, ';'); 
    foreach ($school[$class_name] as $index => $student) {
        $names = explode(' ', $student['name']);
        $lastname = array_shift($names);
        $firstname = implode(' ', $names);
        $gender_code = $student['gender'] === 'fiú' ? 'M' : 'F';
        $student_id = sprintf('%s-%d', $class_name, $index);

        foreach ($student['grades'] as $subject => $grades) {
            $row = [
                $student_id,
                $student['name'],
                $firstname,
                $lastname,
                $gender_code,
                $subject,
                is_array($grades) ? implode(',', $grades) : $grades
            ];
            fputcsv($file, $row, ';'); 
        }
    }
    fclose($file);
    return $filename;
}

#endregion

#region atlag kiszamitas
function calculate_subject_averages($school, $class_name = null) {
    $averages = [];
    
    $classes = $class_name ? [$class_name => $school[$class_name]] : $school;
    
    foreach ($classes as $current_class => $students) {
        $averages[$current_class] = [];
        foreach (DATA['subjects'] as $subject) {
            $grades = [];
            foreach ($students as $student) {
                if ($student['grades'][$subject][0] !== 'nincs jegy') {
                    $grades = array_merge($grades, $student['grades'][$subject]);
                }
            }
            $averages[$current_class][$subject] = count($grades) > 0 ? 
                round(array_sum($grades) / count($grades), 2) : 0;
        }
    }
    
    return $averages;
}

function calculate_student_rankings($school, $class_name = null) {
    $rankings = [];
    $classes = $class_name ? [$class_name => $school[$class_name]] : $school;
    
    foreach ($classes as $current_class => $students) {
        $rankings[$current_class] = [];
        foreach ($students as $student) {
            $total_grades = 0;
            $grade_count = 0;
            foreach ($student['grades'] as $subject => $grades) {
                if ($grades[0] !== 'nincs jegy') {
                    $total_grades += array_sum($grades);
                    $grade_count += count($grades);
                }
            }
            $average = $grade_count > 0 ? round($total_grades / $grade_count, 2) : 0;
            $rankings[$current_class][] = [
                'name' => $student['name'],
                'average' => $average
            ];
        }
        usort($rankings[$current_class], fn($a, $b) => $b['average'] <=> $a['average']);
    }
    
    return $rankings;
}

function calculate_student_subject_averages($student) {
    $averages = [];
    foreach ($student['grades'] as $subject => $grades) {
        if ($grades[0] !== 'nincs jegy') {
            $averages[$subject] = round(array_sum($grades) / count($grades), 2);
        } else {
            $averages[$subject] = 0;
        }
    }
    return $averages;
}

function find_best_worst_classes($school) {
    $class_averages = [];
    foreach ($school as $class_name => $students) {
        $total_grades = 0;
        $grade_count = 0;
        foreach ($students as $student) {
            foreach ($student['grades'] as $grades) {
                if ($grades[0] !== 'nincs jegy') {
                    $total_grades += array_sum($grades);
                    $grade_count += count($grades);
                }
            }
        }
        $class_averages[$class_name] = $grade_count > 0 ? 
            round($total_grades / $grade_count, 2) : 0;
    }
    
    arsort($class_averages);
    return [
        'best' => array_key_first($class_averages),
        'worst' => array_key_last($class_averages),
        'averages' => $class_averages
    ];
}

function calculate_student_average($student) {
    $total_grades = 0;
    $grade_count = 0;
    
    foreach ($student['grades'] as $grades) {
        if ($grades[0] !== 'nincs jegy') {
            $total_grades += array_sum($grades);
            $grade_count += count($grades);
        }
    }
    
    return $grade_count > 0 ? number_format(round($total_grades / $grade_count, 2), 2) : 'N/A';
}
#endregion

#region atlag kiir
function display_subject_averages($averages) {
    echo "<div class='results-container'>";
    echo "<h2>Tantárgyi átlagok</h2>";
    
    foreach ($averages as $class_name => $subjects) {
        echo "<div class='class-results'>";
        echo "<h3>$class_name osztály</h3>";
        echo "<table>";
        echo "<tr><th>Tantárgy</th><th>Átlag</th></tr>";
        foreach ($subjects as $subject => $average) {
            echo "<tr><td>$subject</td><td>" . ($average ?: 'N/A') . "</td></tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    echo "</div>";
}

function display_rankings($rankings) {
    echo "<div class='results-container'>";
    echo "<h2>Tanulói rangsor</h2>";
    
    foreach ($rankings as $class_name => $students) {
        echo "<div class='class-results'>";
        echo "<h3>$class_name osztály</h3>";
        echo "<table>";
        echo "<tr><th>Helyezés</th><th>Név</th><th>Átlag</th></tr>";
        
        foreach (array_slice($students, 0, 3) as $index => $student) {
            echo "<tr class='top-3'><td>" . ($index + 1) . 
                "</td><td>{$student['name']}</td><td>{$student['average']}</td></tr>";
        }
        
        $total = count($students);
        foreach (array_slice($students, -3) as $index => $student) {
            echo "<tr class='bottom-3'><td>" . ($total - 2 + $index) . 
                "</td><td>{$student['name']}</td><td>{$student['average']}</td></tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    echo "</div>";
}

function display_best_worst_classes($results) {
    echo "<div class='results-container'>";
    echo "<h2>Osztályok rangsora</h2>";
    echo "<div class='class-results'>";
    echo "<table>";
    echo "<tr><th>Osztály</th><th>Átlag</th></tr>";
    
    foreach ($results['averages'] as $class => $average) {
        $class_style = '';
        if ($class === $results['best']) $class_style = 'class="best-class"';
        if ($class === $results['worst']) $class_style = 'class="worst-class"';
        echo "<tr $class_style><td>$class</td><td>$average</td></tr>";
    }
    
    echo "</table>";
    echo "</div></div>";
}
#endregion

#region minden

function handle_view($view, $school) {
    if (!isset($view)) {
        print_all_students($school);
        return;
    }

    switch($view) {
        case 'all':
            print_all_students($school);
            break;
        
        case 'subject_averages':
            display_subject_averages_page($school);
            break;
        
        case 'student_rankings':
            display_student_rankings_page($school);
            break;
        
        case 'class_rankings':
            display_class_rankings_page($school);
            break;
        
        default:
            handle_class_view($view, $school);
            break;
    }
}

function display_subject_averages_page($school) {
    $averages = calculate_subject_averages($school);
    display_subject_averages($averages);
    display_export_form('subject_averages');
}

function display_student_rankings_page($school) {
    $rankings = calculate_student_rankings($school);
    display_rankings($rankings);
    display_export_form('student_rankings');
}

function display_class_rankings_page($school) {
    $comparison = find_best_worst_classes($school);
    display_best_worst_classes($comparison);
    display_export_form('class_rankings');
}

function handle_class_view($view, $school) {
    if (array_key_exists($view, $school)) {
        print_class_students($school, $view);
        display_export_form('class', $view);
    } else {
        echo "<p>Ismeretlen nézet.</p>";
    }
}

function display_export_form($type, $class_name = null) {
    echo '<div class="export-container">';
    echo '<form method="POST" class="export-form">';
    if ($class_name) {
        echo '<input type="hidden" name="export_class" value="' . $class_name . '">';
    } else {
        echo '<input type="hidden" name="export_type" value="' . $type . '">';
    }
    echo '<button type="submit">CSV-be exportálás</button>';
    echo '</form>';
    echo '</div>';
}

function handle_export($post_data, $school) {
    if (!isset($post_data['export_type'])) {
        return;
    }

    $export_type = $post_data['export_type'];
    $export_dir = ensure_export_directory();
    $timestamp = date('Y-m-d_Hi');
    $filename = "{$export_type}_{$timestamp}.csv";
    $filepath = $export_dir . '/' . $filename;
    
    $file = fopen($filepath, 'w');
    if (!$file) {
        display_export_error();
        return;
    }

    export_data_to_file($file, $export_type, $school);
    fclose($file);
    display_export_success($filename);
}

function export_data_to_file($file, $export_type, $school) {
    switch($export_type) {
        case 'subject_averages':
            export_subject_averages($file, $school);
            break;
        
        case 'student_rankings':
            export_student_rankings($file, $school);
            break;
        
        case 'class_rankings':
            export_class_rankings($file, $school);
            break;
    }
}

function export_subject_averages($file, $school) {
    $averages = calculate_subject_averages($school);
    fputcsv($file, ['Osztály', 'Tantárgy', 'Átlag'], ';');
    foreach ($averages as $class => $subjects) {
        foreach ($subjects as $subject => $average) {
            fputcsv($file, [$class, $subject, $average], ';');
        }
    }
}

function export_student_rankings($file, $school) {
    $rankings = calculate_student_rankings($school);
    fputcsv($file, ['Osztály', 'Helyezés', 'Név', 'Átlag'], ';');
    foreach ($rankings as $class => $students) {
        foreach ($students as $index => $student) {
            fputcsv($file, [$class, $index + 1, $student['name'], $student['average']], ';');
        }
    }
}

function export_class_rankings($file, $school) {
    $comparison = find_best_worst_classes($school);
    fputcsv($file, ['Osztály', 'Átlag', 'Státusz'], ';');
    foreach ($comparison['averages'] as $class => $average) {
        $status = '';
        if ($class === $comparison['best']) $status = 'Legjobb';
        if ($class === $comparison['worst']) $status = 'Leggyengébb';
        fputcsv($file, [$class, $average, $status], ';');
    }
}

function display_export_success($filename) {
    echo "<p class='export-message success'>Az adatok sikeresen exportálva: $filename</p>";
}

function display_export_error() {
    echo "<p class='export-message error'>Hiba történt az exportálás során.</p>";
}
#endregion
?>

<!DOCTYPE html>
<html lang="hu">
    
<?php echo generate_head(); ?>

<body>
    <div id="kint">
        <div class="button-container" id="bent">
            <h1 class="ubuntu-regular">Horlik Nimród Imre Szakközép Iskola</h1>
            
            <div class="class-buttons">
                <button onclick="window.location.href='?view=all'">Összes tanuló</button>
                <button onclick="window.location.href='?view=11a'">11a osztály</button>
                <button onclick="window.location.href='?view=11b'">11b osztály</button>
                <button onclick="window.location.href='?view=11c'">11c osztály</button>
                <button onclick="window.location.href='?view=12a'">12a osztály</button>
                <button onclick="window.location.href='?view=12b'">12b osztály</button>
                <button onclick="window.location.href='?view=12c'">12c osztály</button>
                <button onclick="window.location.href='?view=subject_averages'">Tantárgyi átlagok</button>
                <button onclick="window.location.href='?view=student_rankings'">Tanulói rangsor</button>
                <button onclick="window.location.href='?view=class_rankings'">Osztályok rangsora</button>
                <form method="POST" class="regenerate-form">
                    <button type="submit" name="regenerate">Újragenerálás</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    handle_view($_GET['view'] ?? null, $school);
    handle_export($_POST, $school);
    ?>
</body>
</html>