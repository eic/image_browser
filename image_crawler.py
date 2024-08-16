import MySQLdb
import os
import sys
import argparse
import json
from utils.ConnectToDB import DBManager
import cv2
from datetime import datetime

connector = None

def scan_image_paths(locations):
    image_paths = []
    for location in locations:
        for root, dirs, files in os.walk(location):
            for file in files:
                if file.endswith(".png"):  # Configurable file type
                    full_path = os.path.join(root, file)
                    print(f'Found image: {full_path}')
                    image_paths.append(full_path)
    return image_paths

def ensure_trailing_slash(path):
    if not path.endswith('/'):
        path += '/'
    return path

def process_image(filepath):
    try:
        plot = os.path.basename(filepath)
        locale, subloc = os.path.split(os.path.dirname(filepath))
        print(f"Scanning sublocation: {subloc}")

        RunNumber = 0  # Always zero
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

        unique_plot_q = f'SELECT ID, ForcedTraining FROM Plots WHERE Plot_Types_ID={PT_ID} AND RunNumber={RunNumber} AND RunPeriod="{RunPeriod}"'
        Plot = connector.FetchAll(unique_plot_q)

        if len(Plot) == 0:
            read_img = cv2.imread(filepath)
            if read_img is None or read_img.size == 0:
                return
            print("Inserting plot")
            insert_q = f'INSERT INTO Plots (Plot_Types_ID, RunPeriod, RunNumber, DateTime) VALUES ({PT_ID}, "{RunPeriod}", {RunNumber}, NOW())'
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

    for image_path in image_paths:
        process_image(image_path)

if __name__ == "__main__":
    main(sys.argv[1:])
