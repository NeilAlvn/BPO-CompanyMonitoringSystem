<!-- Station_generator.php -->
<?php
function generateStationGrid($stationPrefix, $start, $end, $gridRange, $blockWidth = "w-16", $blockHeight = "h-16") {
    // Destructure grid range into columns and rows
    [$totalColumns, $totalRows] = $gridRange;
    
    // Loop through the specified range and generate the grid
    $currentColumn = 1;
    $currentRow = 1;

    for ($i = $start; $i <= $end; $i++) {
        $station = "{$stationPrefix}$i";
        
        // Create a station with the appropriate grid position and custom block size
        echo "<div id='$station' title='$station' 
                class='$blockWidth $blockHeight bg-black rounded-md hover:scale-105 transition-transform cursor-pointer flex items-center justify-center' 
                style='grid-column: $currentColumn; grid-row: $currentRow;'>
                $station
              </div>";

        // Move to the next column
        $currentColumn++;
        
        // If the column exceeds the total columns, move to the next row and reset column to 1
        if ($currentColumn > $totalColumns) {
            $currentColumn = 1;
            $currentRow++;
        }

        // Stop if we've exceeded the total number of rows
        if ($currentRow > $totalRows) {
            break;
        }
    }
}


function generateStation(){
    // Call the function to generate top rows (BS-Station136 to BS-Station129) with 4 columns and 2 rows, and block size of 20x20
    generateStationGrid("BS-Station", 129, 136, [4, 2], "w-20", "h-20");

    // Call the function to generate bottom rows (BS-Station137 to BS-Station144) with 4 columns and 2 rows, and block size of 24x24
    generateStationGrid("BS-Station", 137, 144, [4, 2], "w-24", "h-24");

}
?>
