import MySQLdb
import os
import sys
import argparse
import json
from utils.ConnectToDB import DBManager
import cv2
from datetime import datetime
import json

connector = None

def scan_image_paths(locations):
    image_paths = []
    
    def recursive_scan(root_dir, current_metadata=None):
        for root, dirs, files in os.walk(root_dir):
            metadata_path = os.path.join(root, "metadata.json")
            if os.path.isfile(metadata_path):
                print(f'Found metadata file: {metadata_path}')
                with open(metadata_path, 'r') as f:
                    current_metadata = json.load(f)

            # Process image files in the current directory
            for file in files:
                if file.endswith(".png"):  # ignoring pdfs for now
                    full_path = os.path.join(root, file)
                    print(f'Found image: {full_path}')
                    
                    image_paths.append({
                        "path": full_path,
                        "metadata": current_metadata
                    })

    # Start scanning from each specified location
    for location in locations:
        recursive_scan(location)
    
    return image_paths

def ensure_trailing_slash(path):
    if not path.endswith('/'):
        path += '/'
    return path

def get_super_plot_group_id():
    query = 'SELECT ID FROM PlotGroups WHERE Name = "Pipeline"'
    result = connector.FetchAll(query)
    
    if result:
        super_plot_group_id = result[0]["ID"]
        print(f'Using SuperPlotGroup_ID from PlotGroups where Name="Pipeline": {super_plot_group_id}')
        return super_plot_group_id
    else:
        print("Error: 'Pipeline' entry not found in PlotGroups table.")
        exit(1)

def insert_into_supergroups(plot_group_id, super_plot_group_id):
    supergroup_check_q = f'SELECT ID FROM SuperGroups WHERE PlotGroup_ID = {plot_group_id} AND SuperPlotGroup_ID = {super_plot_group_id}'
    supergroup_check = connector.FetchAll(supergroup_check_q)
    
    if not supergroup_check:
        #insert if it doesn't exist
        supergroup_insert_q = f'''
            INSERT INTO SuperGroups (PlotGroup_ID, SuperPlotGroup_ID)
            VALUES ({plot_group_id},{super_plot_group_id})
        ''' 
        connector.Update(supergroup_insert_q)
        print(f'Inserted PlotGroup_ID {plot_group_id} into SuperGroups with SuperPlotGroup_ID {super_plot_group_id}')
    else:
        print(f'SuperGroup entry already exists for PlotGroup_ID {plot_group_id} and SuperPlotGroup_ID {super_plot_group_id}')

def process_image(filepath, metadata):
    try:
        plot = os.path.basename(filepath)
        locale, subloc = os.path.split(os.path.dirname(filepath))
        print(f"scanning locale:  {locale}")
        print(f"Scanning sublocation: {subloc}")
        
        pipeline_id = None
        plot_group_id = None
        super_plot_group_id = None
        
        #if pipeline is in the path to the image, grab the pipeline number and add it into plot groups else continue
        if 'pipeline' in locale:
            super_plot_group_id = get_super_plot_group_id()
            if super_plot_group_id is None:
                print("Skipping SuperGroups insertion")
                return
            
            locale_parts = locale.split(os.sep)
            for part in locale_parts:
                if part.startswith('pipeline-'):
                    pipeline_id = part.split('pipeline-')[-1]
                    break
                
            if pipeline_id:
                print(f"found pipeline id: {pipeline_id}")
                plot_group_q = f'SELECT ID FROM PlotGroups WHERE Name="{pipeline_id}"'
                PlotGroup = connector.FetchAll(plot_group_q)
                
                if len(PlotGroup) == 0:
                    insert_pg_q = f'INSERT INTO PlotGroups (Name) VALUES ("{pipeline_id}")'
                    print(insert_pg_q)
                    connector.Update(insert_pg_q)
                    
                    PlotGroup = connector.FetchAll(plot_group_q)
                    if PlotGroup:
                        plot_group_id = PlotGroup[0]["ID"]
                        print(f'inserted new PlotGroup with ID: {plot_group_id}')
                    else:
                        print(f"Error: Could not retrieve PlotGroup ID for pipeline {pipeline_id}")
                        return
                        
                else:
                    plot_group_id = PlotGroup[0]['ID']
                    print(f"PlotGroup already exists with ID: {plot_group_id}")
            
                if plot_group_id and super_plot_group_id:
                    print(f'inserting into supergroups {plot_group_id} and {super_plot_group_id}')
                    insert_into_supergroups(plot_group_id, super_plot_group_id)
            else:
                print(f"coud not find pipeline id in locale: {locale}")
        else:
            print('no pipeline in locale')
    

        RunNumber = 0
        RunPeriod = ensure_trailing_slash(f"{locale}/{subloc}")
        Name = plot.rsplit(".", 1)[0]

        print(f"Name of plot: {Name}, Run Period: {RunPeriod}")

        Plot_Type_ID_q = f'SELECT ID FROM Plot_Types WHERE Name="{Name}" AND FileType="png"'
        Plot_Type_ID = connector.FetchAll(Plot_Type_ID_q)
        print(f'Plot type ID query result: {Plot_Type_ID}')

        if len(Plot_Type_ID) != 1:
            return
        else:
            PT_ID = Plot_Type_ID[0]["ID"]
            print(f'Plot type ID: {PT_ID}')

        unique_plot_q = f'SELECT ID FROM Plots WHERE Plot_Types_ID={PT_ID} AND RunNumber={RunNumber} AND RunPeriod="{RunPeriod}"'
        Plot = connector.FetchAll(unique_plot_q)

        if len(Plot) == 0:
            read_img = cv2.imread(filepath)
            if read_img is None or read_img.size == 0:
                return
            print("Inserting plot")
            # Insert into Plots with MetaData as NULL if metadata is None
            metadata_value = "NULL" if metadata is None else f"'{json.dumps(metadata)}'"
            insert_q = f'''
                INSERT INTO Plots (Plot_Types_ID, RunPeriod, RunNumber, InsertDateTime, MetaData)
                VALUES ({PT_ID}, "{RunPeriod}", {RunNumber}, NOW(), {metadata_value})
            '''
            connector.Update(insert_q)
        else:
            print("Plot already inserted")
    except Exception as e:
        print(f"Error processing image {filepath}: {e}")

def main(argv):
    global connector

    ap = argparse.ArgumentParser()
    ap.add_argument("-c", "--config", required=True, help="path to hydra config file")
    args = vars(ap.parse_args())
    configPath = args["config"]

    try:
        with open(configPath) as parms_json:
            parms = json.load(parms_json)
            locations_to_scan = parms["DATA_LOCATION"]["ImageCaches"]
    except Exception as e:
        print(f"Error reading config file: {e}")
        exit(1)

    connector = DBManager(configPath=configPath)
    
    crawler_pidFile = f"/tmp/{str(locations_to_scan[0]).replace('/', '_')}_img_crawler_pid"
    if os.path.exists(crawler_pidFile):
        try:
            with open(crawler_pidFile, "r") as cpidf:
                cpid = cpidf.readline().strip()
                os.kill(int(cpid), 0)
        except OSError:
            pass
        else:
            print("Crawler is already running")
            exit(0)

    with open(crawler_pidFile, 'w') as pidf:
        pidf.write(str(os.getpid()))

    print(f"Scanning: {locations_to_scan}")
    image_paths = scan_image_paths(locations_to_scan)

    for image in image_paths:
        process_image(image["path"], image["metadata"])

if __name__ == "__main__":
    main(sys.argv[1:])
